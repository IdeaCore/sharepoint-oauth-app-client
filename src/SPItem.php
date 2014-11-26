<?php
/**
 * This file is part of the SharePoint OAuth App Client library.
 *
 * @author     Quetzy Garcia <qgarcia@wearearchitect.com>
 * @copyright  2014 Architect 365
 * @link       http://architect365.co.uk
 *
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed
 * with this source code.
 */

namespace WeAreArchitect\SharePoint;

class SPItem extends SPObject implements SPItemInterface
{
	use SPCommonPropertiesTrait;

	/**
	 * SharePoint List
	 *
	 * @access  private
	 */
	private $list = null;

	/**
	 * SharePoint Item constructor
	 *
	 * @access  public
	 * @param   SPList $list  SharePoint List object
	 * @param   array  $json  JSON response from the SharePoint REST API
	 * @param   array  $extra Extra SharePoint Item properties to map
	 * @return  SPItem
	 */
	public function __construct(SPList $list, array $json, array $extra = [])
	{
		parent::__construct([
			'type'  => '__metadata.type',
			'id'    => 'Id',
			'guid'  => 'GUID',
			'title' => 'Title'
		], $extra);

		$this->list = $list;

		$this->hydrate($json);
	}

	/**
	 * Get all SharePoint Items
	 *
	 * @static
	 * @access  public
	 * @param   SPList $list     SharePoint List
	 * @param   array  $settings Instantiation settings
	 * @throws  SPException
	 * @return  array
	 */
	public static function getAll(SPList $list, array $settings = [])
	{
		$defaults = [
			'extra' => [],  // extra SharePoint Item properties to map
			'top'   => 5000 // SharePoint Item threshold
		];

		// overwrite defaults with settings
		$settings = array_merge($defaults, $settings);

		$json = $list->request("_api/web/Lists(guid'".$list->getGUID()."')/items", [
			'headers' => [
				'Authorization' => 'Bearer '.$list->getSPAccessToken(),
				'Accept'        => 'application/json;odata=verbose'
			],

			'query'   => [
				'top' => $settings['top']
			]
		]);

		$items = [];

		foreach ($json['d']['results'] as $item) {
			$items[$item['GUID']] = new static($list, $item, $settings['extra']);
		}

		return $items;
	}

	/**
	 * Get a SharePoint Item by ID
	 *
	 * @static
	 * @access  public
	 * @param   SPList $list  SharePoint List
	 * @param   int    $id    Item ID
	 * @param   array  $extra Extra SharePoint Item properties to map
	 * @throws  SPException
	 * @return  SPItem
	 */
	public static function getByID(SPList $list, $id = 0, array $extra = [])
	{
		if (empty($id)) {
			throw new SPException('The Item ID is empty/not set');
		}

		$json = $list->request("_api/web/Lists(guid'".$list->getGUID()."')/items(".$id.")", [
			'headers' => [
				'Authorization' => 'Bearer '.$list->getSPAccessToken(),
				'Accept'        => 'application/json;odata=verbose'
			]
		]);

		return new static($list, $json['d'], $extra);
	}

	/**
	 * Create a SharePoint Item
	 *
	 * @static
	 * @access  public
	 * @param   SPList $list       SharePoint List
	 * @param   array  $properties SharePoint Item properties (Title, ...)
	 * @param   array  $extra      Extra SharePoint Item properties to map
	 * @throws  SPException
	 * @return  SPItem
	 */
	public static function create(SPList $list, array $properties, array $extra = [])
	{
		$defaults = [
			'__metadata' => [
				'type' => $list->getItemType()
			]
		];

		// overwrite properties with defaults
		$properties = array_merge($properties, $defaults);

		$body = json_encode($properties);

		$json = $list->request("_api/web/Lists(guid'".$list->getGUID()."')/items", [
			'headers' => [
				'Authorization'   => 'Bearer '.$list->getSPAccessToken(),
				'Accept'          => 'application/json;odata=verbose',
				'X-RequestDigest' => (string) $list->getSPFormDigest(),
				'Content-type'    => 'application/json;odata=verbose',
				'Content-length'  => strlen($body)
			],

			'body'    => $body

		], 'POST');

		return new static($list, $json['d'], $extra);
	}

	/**
	 * Update a SharePoint Item
	 *
	 * @access  public
	 * @param   array  $properties SharePoint Item properties (Title, ...)
	 * @throws  SPException
	 * @return  SPItem
	 */
	public function update(array $properties)
	{
		$defaults = [
			'__metadata' => [
				'type' => $this->type
			]
		];

		// overwrite properties with defaults
		$properties = array_merge($properties, $defaults);

		$body = json_encode($properties);

		$this->list->request("_api/web/Lists(guid'".$this->list->getGUID()."')/items(".$this->id.")", [
			'headers' => [
				'Authorization'   => 'Bearer '.$this->list->getSPAccessToken(),
				'Accept'          => 'application/json;odata=verbose',
				'X-RequestDigest' => (string) $this->list->getSPFormDigest(),
				'X-HTTP-Method'   => 'MERGE',
				'IF-MATCH'        => '*',
				'Content-type'    => 'application/json;odata=verbose',
				'Content-length'  => strlen($body)
			],

			'body'    => $body

		], 'POST');

		/**
		 * NOTE: Rehydration is done using the $properties array,
		 * since the SharePoint API does not return a response on
		 * a successful update
		 */
		$this->hydrate($properties, true);

		return $this;
	}

	/**
	 * Delete a SharePoint Item
	 *
	 * @access  public
	 * @throws  SPException
	 * @return  bool
	 */
	public function delete()
	{
		$this->list->request("_api/web/Lists(guid'".$this->list->getGUID()."')/items(".$this->id.")", [
			'headers' => [
				'Authorization'   => 'Bearer '.$this->list->getSPAccessToken(),
				'X-RequestDigest' => (string) $this->list->getSPFormDigest(),
				'IF-MATCH'        => '*',
				'X-HTTP-Method'   => 'DELETE'
			]
		], 'POST');

		return true;
	}
}
