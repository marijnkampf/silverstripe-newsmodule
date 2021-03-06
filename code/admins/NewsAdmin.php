<?php
/**
 * Default admin for the newsmodule.
 * This way, it's less of a clutter in the sitetree.
 * 
 * @package News/blog module
 * @author Simon 'Sphere'
 */
class NewsAdmin extends ModelAdmin {

	private static $managed_models = array(
		'News',
		'Tag',
	);

	private static $url_segment = 'news';

	private static $menu_title = 'News';
	
	private static $menu_icon = '/silverstripe-newsmodule/images/newspaper.png';
	
	public $showImportForm = false;
	
	/**
	 * Add the sortorder to tags. I guess tags are sortable now.
	 * @param Int $id (No idea)
	 * @param FieldList $fields because I can
	 * @return Form $form, because it comes in handy.
	 */
	public function getEditForm($id = null, $fields = null) {
		$form = parent::getEditForm($id, $fields);
		$siteConfig = SiteConfig::current_site_config();
		/**
		 * SortOrder is ignored unless sortable is enabled.
		 */
		if($this->modelClass == "Tag" && $siteConfig->AllowTags){
			$form->Fields()
				->fieldByName('Tag')
				->getConfig()
				->addComponent(
					new GridFieldSortableRows(
						'SortOrder'
					)
				);
		}
		if($this->modelClass == "News" && !$siteConfig->AllowExport){
			$form->Fields()
				->fieldByName("News")
				->getConfig()
				->removeComponentsByType('GridFieldExportButton');
		}
		return $form;
	}

	/**
	 * List only newsitems from current subsite.
	 * @author Marcio Barrientos
	 * @return List $list
	 */
	public function getList() {
		$list = parent::getList();
		$siteConfig = SiteConfig::current_site_config(); // Unused? @Marcio Barrientos
		if($this->modelClass == 'News' && class_exists('Subsite')) {
			$filter = array();
			foreach (NewsHolderPage::get()->filter(array('SubsiteID' => (int) Subsite::currentSubsiteID())) as $holderpage){
				array_push($filter,$holderpage->ID);
			}
			$list = $list->filter('NewsHolderPageID', $filter);
		}

		return $list;
	}
}

