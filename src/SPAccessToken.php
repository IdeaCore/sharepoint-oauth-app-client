<?php
/**
 * This file is part of the SharePoint OAuth App Client library.
 *
 * @author     Quetzy Garcia <qgarcia@wearearchitect.com>
 * @copyright  2014-2015 Architect 365
 * @link       http://architect365.co.uk
 *
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed
 * with this source code.
 */

namespace WeAreArchitect\SharePoint;

use Carbon\Carbon;
use Exception;
use JWT\Authentication\JWT;
use Serializable;

class SPAccessToken extends SPObject implements Serializable
{
    /**
     * Access token
     *
     * @access  protected
     */
    protected $token = null;

    /**
     * Expire date
     *
     * @access  protected
     */
    protected $expires = null;

    /**
     * Hydration handler
     *
     * @access  protected
     * @param   array     $json    JSON response from the SharePoint REST API
     * @param   bool      $missing Allow missing properties?
     * @throws  SPException
     * @return  void
     */
    protected function hydrate(array $json, $missing = false)
    {
        parent::hydrate($json, $missing);

        $this->expires = Carbon::createFromTimestamp($this->expires);
    }

    /**
     * SharePoint Access Token constructor
     *
     * @access  public
     * @param   array  $json  JSON response from the SharePoint REST API
     * @param   array  $extra Extra SharePoint Access Token properties to map
     * @throws  SPException
     * @return  SPAccessToken
     */
    public function __construct(array $json, array $extra = [])
    {
        parent::__construct([
            'token'   => 'access_token',
            'expires' => 'expires_on'
        ], $extra);

        $this->hydrate($json);
    }

    /**
     * @{inheritdoc}
     */
    public function toArray()
    {
        return [
            'token'   => $this->token,
            'expires' => $this->expires,
            'extra'   => $this->extra
        ];
    }

    /**
     * Serialize SharePoint Access Token
     *
     * @access  public
     * @return  string
     */
    public function serialize()
    {
        return serialize([
            $this->token,
            $this->expires->getTimestamp()
        ]);
    }

    /**
     * Recreate SharePoint Access Token
     *
     * @access  public
     * @param   string $serialized
     * @return  void
     */
    public function unserialize($serialized = null)
    {
        list($this->token, $this->expires) = unserialize($serialized);

        $this->expires = Carbon::createFromTimeStamp($this->expires);
    }

    /**
     * SharePoint Access Token string value
     *
     * @access  public
     * @return  string
     */
    public function __toString()
    {
        return $this->token;
    }

    /**
     * Create a SharePoint Access Token (User Context Token)
     *
     * @static
     * @access  public
     * @param   SPSite $site          SharePoint Site
     * @param   string $context_token Context Token
     * @param   array  $extra         Extra SharePoint Access Token properties to map
     * @throws  SPException
     * @return  SPAccessToken
     */
    public static function createFromUser(SPSite $site, $context_token = null, array $extra = [])
    {
        $config = $site->getConfig();

        if (empty($context_token)) {
            throw new SPException('The Context Token is empty/not set');
        }

        if (empty($config['secret'])) {
            throw new SPException('The Secret is empty/not set');
        }

        try {
            $jwt = JWT::decode($context_token, $config['secret'], false);
        } catch (Exception $e) {
            throw new SPException('Unable to decode the Context Token', 0, $e);
        }

        // get URL hostname
        $hostname = parse_url($config['url'], PHP_URL_HOST);

        // build resource
        $resource = str_replace('@', '/'.$hostname.'@', $jwt->appctxsender);

        // decode application context
        $oauth2 = json_decode($jwt->appctx);

        $json = $site->request($oauth2->SecurityTokenServiceUri, [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],

            // the POST body must be passed as a query string
            'body'    => http_build_query([
                'grant_type'    => 'refresh_token',
                'client_id'     => $jwt->aud,
                'client_secret' => $config['secret'],
                'refresh_token' => $jwt->refreshtoken,
                'resource'      => $resource
            ])
        ], 'POST');

        return new static($json, $extra);
    }

    /**
     * Create a SharePoint Access Token (App only policy)
     *
     * @static
     * @access  public
     * @param   SPSite $site  SharePoint Site
     * @param   array  $extra Extra SharePoint Access Token properties to map
     * @throws  SPException
     * @return  SPAccessToken
     */
    public static function createFromAOP(SPSite $site, array $extra = [])
    {
        $config = $site->getConfig();

        if (empty($config['secret'])) {
            throw new SPException('The Secret is empty/not set');
        }

        if (empty($config['acs'])) {
            throw new SPException('The Azure Access Control Service URL is empty/not set');
        }

        if (! filter_var($config['acs'], FILTER_VALIDATE_URL)) {
            throw new SPException('The Azure Access Control Service URL is invalid');
        }

        if (empty($config['client_id'])) {
            throw new SPException('The Client ID is empty/not set');
        }

        if (empty($config['resource'])) {
            throw new SPException('The Resource is empty/not set');
        }

        $json = $site->request($config['acs'], [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],

            // the POST body must be passed as a query string
            'body'    => http_build_query([
                'grant_type'    => 'client_credentials',
                'client_id'     => $config['client_id'],
                'client_secret' => $config['secret'],
                'resource'      => $config['resource']
            ])
        ], 'POST');

        return new static($json, $extra);
    }

    /**
     * Check if the SharePoint Access Token has expired
     *
     * @access  public
     * @return  bool
     */
    public function hasExpired()
    {
        return $this->expires->isPast();
    }

    /**
     * Get the SharePoint Access Token expire date
     *
     * @access  public
     * @return  Carbon
     */
    public function expireDate()
    {
        return $this->expires;
    }
}
