<?php
/**
 * The news.
 * Sit back and relax, this might take a while.
 * History is NOT supported. Only the URLSegment is being tracked. This makes it a bit more simplistic.
 * 
 * Ow, yes, translatable... Can I ponder on that please?
 * 
 * @package News/blog module
 * @author Simon 'Sphere' 
 */
class News extends DataObject { // implements IOGObject{ // optional for OpenGraph support

	public static $db = array(
		'Title' => 'Varchar(255)',
		// Author is a troublemaker. Please tell me, 
		// should I either auto-set the username from currentmember, 
		// or use the textfield I'm using now (LAZY!)
		'Author' => 'Varchar(255)',
		'URLSegment' => 'Varchar(255)',
		'Content' => 'HTMLText',
		'Lang' => 'Boolean(false)',
		'Tweeted' => 'Boolean(false)',
		'Live' => 'Boolean(true)',
	);
	
	public static $has_one = array(
		'NewsHolderPage' => 'NewsHolderPage',
		'Impression' => 'Image',
	);
	
	public static $has_many = array(
		'Comments' => 'Comment',
		'Renamed' => 'Renamed',
	);
	
	public static $belongs_many_many = array(
		'Tags' => 'Tag',
	);

	public static $default_sort = 'Created DESC';

	/**
	 * Define singular name translatable
	 * @return type Singular name
	 */
	public function singular_name() {
		if (_t($this->class . '.SINGULARNAME')) {
			return _t($this->class . '.SINGULARNAME');
		} else {
			return parent::singular_name();
		} 
	}
	
	/**
	 * Define plural name translatable
	 * @return type Plural name
	 */
	public function plural_name() {
		if (_t($this->class . '.SINGULARNAME')) {
			return _t($this->class . '.SINGULARNAME');
		} else {
			return parent::plural_name();
		}   
	}
	
	/**
	 * Define sumaryfields;
	 * @todo obey translations
	 * @return string Make summaryfields translatable
	 */
	public function summaryFields() {
		$summaryFields = array(
			'Title' => 'Titel',
			'Author' => 'Author',
			'Created' => 'Created',
		);
		if(array_search('Translatable', SiteTree::$extensions)){
			$summaryFields['getLocale'] = _t($this->class . '.LANG', 'Language');
		}
		$this->extend('summary_fields', $summaryFields);

		return $summaryFields;
	}
	
	/**
	 * Define translatable searchable fields
	 * @return array Searchable Fields translatable
	 */
	public function searchableFields(){
		$searchableFields = array(
			'Title' => array(
				'field'  => 'TextField',
				'filter' => 'PartialMatchFilter',
				'title'  => _t($this->class . '.TITLE','Title')
			),
		);
		if(array_search('Translatable', SiteTree::$extensions)){
			$searchableFields['NewsHolderPageID'] = array(
				'field' => 'DropdownField',
				'title' => _t($this->class . '.LOCALE', 'Language'),
				'filter' => 'ExactMatchFilter',

			);
		}

		$this->extend('searchable_fields', $searchableFields);
		
		return $searchableFields;
	}

	/**
	 * Why do I have to do this???
	 * We can't feed an array directly into the searchfields, so, we have to make a workaround.
	 * Buh...
	 * @todo cleanup and make it working.
	 * @param type $_params
	 * @return type 
	 */
	public function scaffoldSearchFields($_params = null){
		$fields = parent::scaffoldSearchFields();
		if(array_search('Translatable', SiteTree::$extensions)){
			$data = new SQLQuery();
			$data->select(array('ID', 'Locale'));
			$data->from = array('SiteTree');
			$data->where = array('ClassName = \'NewsHolderPage\'', 'Status = \'Published\'');
			$array = $data->execute();
			if(count($array) > 0){
				if(count($array->map()) > 1){
					$locales = i18n::get_common_locales();
					$return = array('' => _t($this->class . '.SELECTSOME', '--Select a locale--'));
					$array = $array->map('ID', 'Locale');
					foreach($array as $key => $value){
						if(substr($value, 0, 2) != '--'){
							$return[$key] = $locales[$value];
						}
					}
					unset($value);
				}
			}


			if(count($array) > 1){
				foreach($fields->items as $item => $field){
					if($field->name == 'NewsHolderPageID'){
						$field->source = $return;
					}
				}
			}
		}
		return $fields;
	}
	
	public function getCMSFields() {
		$fields = parent::getCMSFields();
		
		/**
		 * remove all, we want translatable fieldlabels, and specific inputtypes
		 */
		$fields->removeFieldsFromTab(
			'Root.Main', 
			array_keys(
				self::$db
			)
		);
		$fields->removeFieldsFromTab(
			'Root.Main', 
			array(
				'NewsHolderPageID',
				'Impression',
			)
		);
		$fields->removeFieldFromTab('Root', 'Tags');
		$fields->addFieldsToTab('Root.Main', 
			array(
				$text = TextField::create('Title', _t($this->class . '.TITLE', 'Title')),
				$html = HTMLEditorField::create('Content', _t($this->class . '.CONTENT', 'Content')),
				$auth = TextField::create('Author', _t($this->class . '.AUTHOR', 'Author')),
				$live = CheckboxField::create('Live', _t($this->class . '.PUSHLIVE', 'Published')),
				$uplo = UploadField::create('Impression', _t($this->class . '.IMPRESSION', 'Impression')),
				$tags = CheckboxSetField::create('Tags', 'Tags', Tag::get()->map('ID', 'Title')),
			)
		);
		
		/**
		* add searcher tab, this is beta!
		* It's connected to another, SS2.4.x module of me. You can ignore it for now.
		* @todo Fix the searcher :'( That's gonna be a hell of a job :'(
		*/
		$fields->addFieldToTab(
			'Root',
			new Tab(
				'Searcher', // name
				_t('SearcherDecorator.TAB_SEARCHER', 'Search *NYT*'), // title
				LiteralField::create(
					'SearcherIntro',
					'<p>' . _t(
						'SearcherDecorator.SEARCHER_INTRO',
						'Specify keywords for the sitesearch *NYT*'
					) . '</p>'
				),
				TextareaField::create('SearchKeywords', _t('SearcherDecorator.KEYWORDS', 'Keywords *NYT*'))
			)
		);


		return($fields);
	}
	
	/**
	 * Setup available locales.
	 * Yes, again, this is beta and not working yet :(
	 * @return type 
	 */
	public function getLocale(){
		if($this->NewsHolderPage()->ID){
			$parent = SiteTree::get()->filter(array('ID' => $this->NewsHolderPage()->ID))->first();
			$locales = i18n::get_common_locales();
			if($parent->Locale){
				return $locales[$parent->Locale];
			}
		}
	}

	/**
	 * Free guess on what this button does.
	 */
	public function Link() {
		if ($newsHolderPage = SiteTree::get()->filter(array("ClassName" => 'NewsHolderPage'))->first()) {
			return($newsHolderPage->Link('show').'/'.$this->URLSegment);
		}
	}

	/**
	 * This is a funny one... why did I do this again?
	 * Anyway, setup URLSegment. Note, IT DOES NOT CHECK FOR DOUBLES! WHY NOT?!
	 * I don't know actually... I think I forgot :(
	 * The holder-page ID should be set if translatable, otherwise, we just select the first available one. 
	 */
	public function onBeforeWrite(){
		parent::onBeforeWrite();
		if(!$this->NewsHolderPageID){
			$page = NewsHolderPage::get()->first();
			$this->NewsHolderPageID = $page->ID;
		}
		if (!$this->URLSegment || ($this->isChanged('Title') && !$this->isChanged('URLSegment'))){
			if($this->ID > 0){
				$Renamed = new Renamed();
				$Renamed->OldLink = $this->URLSegment;
				$Renamed->NewsID = $this->ID;
				$Renamed->write();
			}
			$this->URLSegment = singleton('SiteTree')->generateURLSegment($this->Title);
			if(strpos($this->URLSegment, 'page-') === false){
				$nr = 1;
				while($this->LookForExistingURLSegment($this->URLSegment)){
					$this->URLSegment .= '-'.$nr++;
				}
			}
		}
	}
	
	/**
	 * Ehhhh, we kinda need to include the tweeting-system here. 
	 */
	public function onAfterWrite(){
		parent::onAfterWrite();
		$siteConfig = SiteConfig::current_site_config();
		/**
		 * Should we tweet (and even more important, CAN we tweet?) 
		 * This is related to another module of mine.
		 * Check it at my repos: Silverstripe-Social
		 * @todo move this feature to the Social-module and address it from here. Cleaner code.
		 */
		if($this->Live && !$this->Tweeted && $siteConfig->TweetOnPost){
			if($siteConfig->ConsumerKey && $siteConfig->ConsumerSecret && $siteConfig->OAuthToken && $siteConfig->OAuthTokenSecret){
				$TweetText = $siteConfig->TweetText;
				$TweetText = str_replace('$Title', $this->Title, $TweetText);
				// Max length is 120 characters, since the URL will be 20 characters long with t.co, 
				// so, let's make that happen.
				if(strlen($TweetText) > 120){
					$TweetText = substr($TweetText, 0, 116).'... '.$this->AbsoluteLink();
				}
				else{
					$TweetText = $TweetText.' '.$this->AbsoluteLink();
				}
				$conn = new TwitterOAuth(
					$siteConfig->ConsumerKey,
					$siteConfig->ConsumerSecret,
					$siteConfig->OAuthToken,
					$siteConfig->OAuthTokenSecret
				);
				$tweetData = array(
					'status' => $TweetText,
				);
				$postResult = $conn->post('statuses/update', $tweetData);
				$this->Tweeted = true;
				$this->write();
			}
		}
	}
	
	/**
	 * This is quite handy, for meta-tags and such.
	 * @param type $action string, the added URLSegment, the actual function that'll return the news.
	 * @return type link. To the item.
	 */
	public function AbsoluteLink($action = 'show/'){
		if($Page = $this->NewsHolderPage()){
			return(Director::absoluteURL($Page->Link($action)). $this->URLSegment);
		}		
	}
		
	/**
	 * All the upcoming OG-functions are related to the OG module.
	 * This bugs in live, works in development. Shoot me?
	 * @return type image, or, if not available, it's holder-page's image.
	 */
	public function getOGImage(){
		if($this->Impression()->ID > 0){
			return Director::getAbsFile($this->Impression()->Filename);
		}
		else{
			return Director::getAbsFile($this->NewsHolderPage()->Impression()->Filename);
		}
	}
	
	/**
	 * Guess
	 * @return type String
	 */
	public function getOGTitle(){
		return $this->Title;
	}
	
	/**
	 * Why does this, again, not work on live, but does it work on dev?
	 * @param type $includeTitle boolean
	 * @return string of a whole heap of meta-data
	 */
	public function MetaTags($includeTitle = true){
		$tags = "";
		$tags .= "<meta name=\"keywords\" content=\"" . Convert::raw2att($this->NewsHolderPage()->MetaKeywords . ',' . str_replace(' ', ',',$this->Title)) . "\" />\n";
		$tags .= "<meta name=\"description\" content=\"" . Convert::raw2att($this->NewsHolderPage()->MetaDescription . ' ' . $this->Title) . "\" />\n";
		
		if($this->ExtraMeta) { 
			$tags .= $this->ExtraMeta . "\n";
		} 
		
		if(Permission::check('CMS_ACCESS_CMSMain') && in_array('CMSPreviewable', class_implements($this))) {
			$tags .= "<meta name=\"x-page-id\" content=\"{$this->ID}\" />\n";
			$tags .= "<meta name=\"x-cms-edit-link\" content=\"" . $this->CMSEditLink() . "\" />\n";
		}
		$this->extend('MetaTags', $tags);
		return $tags;
	}
	
	/**
	 * test whether the URLSegment exists already on another Newsitem
	 * @return boolean if urlsegment already exists yes or no.
	 */
	public function LookForExistingURLSegment($URLSegment) {
		return(News::get()->filter(array("URLSegment" => $URLSegment))->exclude(array("ID" => $this->ID))->count() != 0);
	}
	
	/**
	 * Ehhh, this needs fixing for SS3.
	 * So yes, you can.
	 */
	public function canCreate($member = null) {
		return(true);
	}

	public function canEdit($member = null) {
		return(true);
	}

	public function canDelete($member = null) {
		return(true);
	}

	public function canView($member = null) {
		return(true);
	}

}
