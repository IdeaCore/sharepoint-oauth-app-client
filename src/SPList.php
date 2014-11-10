<?php
/**
 * This file is part of the SharePoint OAuth App Client package.
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

class SPList implements SPListInterface
{
	use SPListTrait;

	/**
	 * List Template Types (SharePoint 2013)
	 *
	 * @link http://msdn.microsoft.com/en-us/library/office/microsoft.sharepoint.client.listtemplatetype%28v=office.15%29.aspx
	 */
	const TPL_GENERICLIST     = 100; // Custom list
	const TPL_DOCUMENTLIBRARY = 101; // Document library
	const TPL_SURVEY          = 102; // Survey
	const TPL_LINKS           = 103; // Links
	const TPL_ANNOUNCEMENTS   = 104; // Announcements
	const TPL_CONTACTS        = 105; // Contacts
	const TPL_EVENTS          = 106; // Calendar
	const TPL_TASKS           = 107; // Tasks
	const TPL_DISCUSSIONBOARD = 108; // Discussion board
	const TPL_PICTURELIBRARY  = 109; // Picture library

	/**
	 * Fetchable List Types
	 */
	public static $fetchable = [
		self::TPL_GENERICLIST,
		self::TPL_DOCUMENTLIBRARY,
		self::TPL_SURVEY,
		self::TPL_LINKS,
		self::TPL_ANNOUNCEMENTS,
		self::TPL_CONTACTS,
		self::TPL_EVENTS,
		self::TPL_TASKS,
		self::TPL_DISCUSSIONBOARD,
		self::TPL_PICTURELIBRARY
	];

	/**
	 * List Field Types (SharePoint 2013)
	 *
	 * @link http://msdn.microsoft.com/en-us/library/office/microsoft.sharepoint.client.fieldtype%28v=office.15%29.aspx
	 */
	const FLD_INTEGER          = 1;  // Field contains an integer value
	const FLD_TEXT             = 2;  // Field contains a single line of text
	const FLD_NOTE             = 3;  // Field contains multiple lines of text
	const FLD_DATETIME         = 4;  // Field contains a date and time value or a date-only value
	const FLD_COUNTER          = 5;  // Field contains a monotonically increasing integer
	const FLD_CHOICE           = 6;  // Field contains a single value from a set of specified values
	const FLD_LOOKUP           = 7;  // Field is a lookup field
	const FLD_BOOLEAN          = 8;  // Field contains a Boolean value
	const FLD_NUMBER           = 9;  // Field contains a floating-point number value
	const FLD_CURRENCY         = 10; // Field contains a currency value
	const FLD_URL              = 11; // Field contains a URI and an optional description of the URI
	const FLD_COMPUTED         = 12; // Field is a computed field
	const FLD_THREADING        = 13; // Field indicates the thread for a discussion item in a threaded view of a discussion board
	const FLD_GUID             = 14; // Field contains a GUID value
	const FLD_MULTICHOICE      = 15; // Field contains one or more values from a set of specified values
	const FLD_GRIDCHOICE       = 16; // Field contains rating scale values for a survey list
	const FLD_CALCULATED       = 17; // Field is a calculated field
	const FLD_FILE             = 18; // Field contains the leaf name of a document as a value
	const FLD_ATTACHMENTS      = 19; // Field indicates whether the list item has attachments
	const FLD_USER             = 20; // Field contains one or more users and groups as values
	const FLD_RECURRENCE       = 21; // Field indicates whether a meeting in a calendar list recurs
	const FLD_CROSSPROJECTLINK = 22; // Field contains a link between projects in a Meeting Workspace site
	const FLD_MODSTAT          = 23; // Field indicates moderation status
	const FLD_ERROR            = 24; // Field type was set to an invalid value
	const FLD_CONTENTPLID      = 25; // Field contains a content type identifier as a value
	const FLD_PAGESEPARATOR    = 26; // Field separates questions in a survey list onto multiple pages
	const FLD_THREADINDEX      = 27; // Field indicates the position of a discussion item in a threaded view of a discussion board
	const FLD_WORKFLOWSTATUS   = 28; // Field indicates the status of a workflow instance on a list item
	const FLD_ALLDAYEVENT      = 29; // Field indicates whether a meeting in a calendar list is an all-day event
	const FLD_WORKFLOWEVENTPL  = 30; // Field contains the most recent event in a workflow instance

	/**
	 * List Template Type
	 *
	 * @access  private
	 */
	private $template = 0;

	/**
	 * List Item Entity Type Full Name
	 *
	 * @access  private
	 */
	private $item_type = null;

	/**
	 * List Description
	 *
	 * @access  private
	 */
	private $description = null;

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
		$this->fill($json, [
			'template'    => 'BaseTemplate',
			'type'        => '__metadata.type',
			'item_type'   => 'ListItemEntityTypeFullName',
			'guid'        => 'Id',
			'title'       => 'Title',
			'description' => 'Description'
		], $missing);
	}

	/**
	 * SharePoint List constructor
	 *
	 * @access  public
	 * @param   SPSite $site  SharePoint Site
	 * @param   array  $json  JSON response from the SharePoint REST API
	 * @param   bool   $fetch Fetch SharePoint Items?
	 * @return  SPList
	 */
	public function __construct(SPSite &$site, array $json, $fetch = false)
	{
		$this->site = $site;

		$this->hydrate($json);

		if ($fetch) {
			$this->getSPItems();
		}
	}

	/**
	 * Get List Template Type
	 *
	 * @access  public
	 * @return  string
	 */
	public function getTemplate()
	{
		return $this->template;
	}

	/**
	 * Get List Description
	 *
	 * @access  public
	 * @return  string
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * Get the List Item Entity Type Full Name
	 *
	 * @access  public
	 * @return  string
	 */
	public function getItemType()
	{
		return $this->item_type;
	}

	/**
	 * Get all SharePoint Lists
	 *
	 * @static
	 * @access  public
	 * @param   SPSite $site  SharePoint Site
	 * @param   bool   $fetch Fetch SharePoint Items?
	 * @throws  SPException
	 * @return  array
	 */
	public static function getAll(SPSite &$site, $fetch = false)
	{
		$json = $site->request('_api/web/Lists', [
			'headers' => [
				'Authorization' => 'Bearer '.$site->getSPAccessToken(),
				'Accept'        => 'application/json;odata=verbose'
			]
		]);

		$lists = [];

		foreach ($json['d']['results'] as $list) {
			// exclude lists the user shouldn't work with
			if (in_array($list['BaseTemplate'], static::$fetchable)) {
				$lists[$list['Id']] = new static($site, $list, $fetch);
			}
		}

		return $lists;
	}

	/**
	 * Get a SharePoint List by GUID
	 *
	 * @static
	 * @access  public
	 * @param   SPSite $site  SharePoint Site
	 * @param   string $guid  SharePoint List GUID
	 * @param   bool   $fetch Fetch SharePoint Items?
	 * @throws  SPException
	 * @return  array
	 */
	public static function getByGUID(SPSite &$site, $guid = null, $fetch = false)
	{
		$json = $site->request("_api/web/Lists(guid'".$guid."')", [
			'headers' => [
				'Authorization' => 'Bearer '.$site->getSPAccessToken(),
				'Accept'        => 'application/json;odata=verbose'
			]
		]);

		return new static($site, $json['d'], $fetch);
	}

	/**
	 * Get a SharePoint List by Title
	 *
	 * @static
	 * @access  public
	 * @param   SPSite $site  SharePoint Site
	 * @param   string $title SharePoint List Title
	 * @param   bool   $fetch Fetch SharePoint Items?
	 * @throws  SPException
	 * @return  array
	 */
	public static function getByTitle(SPSite &$site, $title = null, $fetch = false)
	{
		$json = $site->request("_api/web/Lists/GetByTitle('".$title."')", [
			'headers' => [
				'Authorization' => 'Bearer '.$site->getSPAccessToken(),
				'Accept'        => 'application/json;odata=verbose'
			]
		]);

		return new static($site, $json['d'], $fetch);
	}

	/**
	 * Create a SharePoint List
	 *
	 * @static
	 * @access  public
	 * @param   SPSite $site       SharePoint Site
	 * @param   array  $properties SharePoint List properties (Title, Description, ...)
	 * @throws  SPException
	 * @return  SPList
	 */
	public static function create(SPSite &$site, array $properties)
	{
		$defaults = [
			'__metadata' => [
				'type' => 'SP.List'
			],
			'AllowContentTypes'   => true,
			'ContentTypesEnabled' => true,
			'BaseTemplate'        => static::TPL_DOCUMENTLIBRARY
		];

		// overwrite defaults with properties
		$properties = array_merge($defaults, $properties);

		$body = json_encode($properties);

		$json = $site->request('_api/web/Lists', [
			'headers' => [
				'Authorization'   => 'Bearer '.$site->getSPAccessToken(),
				'Accept'          => 'application/json;odata=verbose',
				'X-RequestDigest' => (string) $site->getSPFormDigest(),
				'Content-type'    => 'application/json;odata=verbose',
				'Content-length'  => strlen($body)
			],

			'body'    => $body
		], 'POST');

		return new static($site, $json['d']);
	}

	/**
	 * Update a SharePoint List
	 *
	 * @access  public
	 * @param   array  $properties SharePoint List properties (Title, Description, ...)
	 * @throws  SPException
	 * @return  SPList
	 */
	public function update(array $properties)
	{
		$defaults = [
			'__metadata' => [
				'type' => 'SP.List'
			]
		];

		// overwrite properties with defaults
		$properties = array_merge($properties, $defaults);

		$body = json_encode($properties);

		$this->request("_api/web/Lists(guid'".$this->guid."')", [
			'headers' => [
				'Authorization'   => 'Bearer '.$this->getAccessToken(),
				'Accept'          => 'application/json;odata=verbose',
				'X-RequestDigest' => (string) $this->getFormDigest(),
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
	 * Delete a List and all it's content
	 *
	 * @access  public
	 * @throws  SPException
	 * @return  bool true if the List was deleted
	 */
	public function delete()
	{
		$this->request("_api/web/Lists(guid'".$this->guid."')", [
			'headers' => [
				'Authorization'   => 'Bearer '.$this->getAccessToken(),
				'Accept'          => 'application/json;odata=verbose',
				'X-RequestDigest' => (string) $this->getFormDigest(),
				'X-HTTP-Method'   => 'DELETE',
				'IF-MATCH'        => '*'
			]
		], 'POST');

		return true;
	}

	/**
	 * Create a SharePoint Field
	 *
	 * @access  public
	 * @param   array  $properties Field properties (Title, FieldTypeKind, ...)
	 * @throws  SPException
	 * @return  string SharePoint List Field id
	 */
	public function createSPField(array $properties)
	{
		$defaults = [
			'__metadata' => [
				'type' => 'SP.Field'
			],
			'FieldTypeKind'       => static::FLD_TEXT,
			'Required'            => false,
			'EnforceUniqueValues' => false
		];

		// overwrite defaults with properties
		$properties = array_merge($defaults, $properties);

		$body = json_encode($properties);

		$json = $this->request("_api/web/Lists(guid'".$this->guid."')/Fields", [
			'headers' => [
				'Authorization'   => 'Bearer '.$this->getAccessToken(),
				'Accept'          => 'application/json;odata=verbose',
				'X-RequestDigest' => (string) $this->getFormDigest(),
				'Content-type'    => 'application/json;odata=verbose',
				'Content-length'  => strlen($body)
			],

			'body'    => $body
		], 'POST');

		return $json['d']['Id'];
	}

	/**
	 * Get the SharePoint List Item count
	 *
	 * @access  public
	 * @throws  SPException
	 * @return  int SharePoint Item count
	 */
	public function getSPItemCount()
	{
		$json = $this->request("_api/web/Lists(guid'".$this->guid."')/itemCount", [
			'headers' => [
				'Authorization' => 'Bearer '.$this->getAccessToken(),
				'Accept'        => 'application/json;odata=verbose'
			]
		]);

		return $json['d']['ItemCount'];
	}

	/**
	 * Get all SharePoint Items
	 *
	 * @static
	 * @access  public
	 * @return  array
	 */
	public function getSPItems()
	{
		$this->items = SPItem::getAll($this);

		return $this->items;
	}

	/**
	 * Get SharePoint Item by ID
	 *
	 * @static
	 * @access  public
	 * @param   int    $id Item ID
	 * @return  SPItem
	 */
	public function getSPItem($id = 0)
	{
		$item = SPItem::getByID($this, $id);

		$this[] = $item;

		return $item;
	}

	/**
	 * Create a SharePoint Item
	 *
	 * @access  public
	 * @param   array  $properties List properties (Title, ...)
	 * @throws  SPException
	 * @return  array
	 */
	public function createSPItem(array $properties)
	{
		$item = SPItem::create($this, $properties);

		$this[] = $item;

		return $item;
	}

	/**
	 * Update a SharePoint Item
	 *
	 * @access  public
	 * @param   string $index      SharePoint Item index
	 * @param   array  $properties SharePoint Item properties (Title, ...)
	 * @return  SPItem
	 */
	public function updateSPItem($index = null, array $properties)
	{
		$item = $this[$index]->update($properties);

		$this[] = $item;

		return $item;
	}

	/**
	 * Delete a SharePoint Item
	 *
	 * @access  public
	 * @param   string $index SharePoint Item index
	 * @throws  SPException
	 * @return  boolean true if the SharePoint Item was deleted
	 */
	public function deleteSPItem($index = null)
	{
		if ($deleted = $this[$index]->delete()) {
			unset($this[$index]);
		}

		return $deleted;
	}
}
