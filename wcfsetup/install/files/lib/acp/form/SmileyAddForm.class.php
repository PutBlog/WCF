<?php
namespace wcf\acp\form;
use wcf\data\category\Category;
use wcf\data\category\CategoryNodeTree;
use wcf\data\package\PackageCache;
use wcf\data\smiley\SmileyAction;
use wcf\data\smiley\SmileyEditor;
use wcf\form\AbstractForm;
use wcf\system\exception\UserInputException;
use wcf\system\language\I18nHandler;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Shows the smiley add form.
 * 
 * @author	Tim Duesterhus
 * @copyright	2001-2013 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.bbcode
 * @subpackage	acp.form
 * @category	Community Framework
 */
class SmileyAddForm extends AbstractForm {
	/**
	 * @see	wcf\page\AbstractPage::$activeMenuItem
	 */
	public $activeMenuItem = 'wcf.acp.menu.link.smiley.add';
	
	/**
	 * @see	wcf\page\AbstractPage::$templateName
	 */
	public $templateName = 'smileyAdd';
	
	/**
	 * @see	wcf\page\AbstractPage::$neededPermissions
	 */
	public $neededPermissions = array('admin.content.smiley.canManageSmiley');
	
	/**
	 * primary smiley code
	 * @var	string
	 */
	public $smileyCode = '';
	
	/**
	 * showorder value
	 * @var	integer
	 */
	public $showOrder = 0;
	
	/**
	 * categoryID value
	 * @var	integer
	 */
	public $categoryID = 0;
	
	/**
	 * smileyTitle
	 * @var	string
	 */
	public $smileyTitle = '';
	
	/**
	 * aliases value
	 * @var	string
	 */
	public $aliases = '';
	
	/**
	 * node tree with available smiley categories
	 * @var	wcf\data\category\CategoryNodeTree
	 */
	public $categoryNodeTree = null;
	
	/**
	 * @see	wcf\page\IPage::assignVariables()
	 */
	public function assignVariables() {
		parent::assignVariables();
		
		I18nHandler::getInstance()->assignVariables();
		
		WCF::getTPL()->assign(array(
			'action' => 'add',
			'smileyTitle' => $this->smileyTitle,
			'showOrder' => $this->showOrder,
			'categoryID' => $this->categoryID,
			'smileyCode' => $this->smileyCode,
			'aliases' => $this->aliases,
			'categoryNodeList' => $this->categoryNodeTree->getIterator()
		));
	}
	
	/**
	 * @see	wcf\page\IPage::readData()
	 */
	public function readData() {
		parent::readData();
		
		$this->categoryNodeTree = new CategoryNodeTree('com.woltlab.wcf.bbcode.smiley', 0, true);
	}
	
	/**
	 * @see	\wcf\page\IPage::readParameters()
	 */
	public function readParameters() {
		parent::readParameters();
		
		I18nHandler::getInstance()->register('smileyTitle');
	}
	
	
	/**
	 * @see	wcf\page\IForm::readFormParameters()
	 */
	public function readFormParameters() {
		parent::readFormParameters();
		
		I18nHandler::getInstance()->readValues();
		
		if (I18nHandler::getInstance()->isPlainValue('smileyTitle')) $this->smileyTitle = I18nHandler::getInstance()->getValue('smileyTitle');
		
		if (isset($_POST['showOrder'])) {
			$this->showOrder = intval($_POST['showOrder']);
		}
		if (isset($_POST['smileyCode'])) {
			$this->smileyCode = StringUtil::trim($_POST['smileyCode']);
		}
		if (isset($_POST['categoryID'])) {
			$this->categoryID = intval($_POST['categoryID']);
		}
		if (isset($_POST['aliases'])) {
			$this->aliases = StringUtil::unifyNewlines(StringUtil::trim($_POST['aliases']));
		}
	}
	
	/**
	 * @see	wcf\page\IForm::save()
	 */
	public function save() {
		parent::save();
		
		$this->objectAction = new SmileyAction(array(), 'create', array(
			'data' => array(
				'smileyTitle' => $this->smileyTitle,
				'smileyCode' => $this->smileyCode,
				'showOrder' => $this->showOrder,
				'categoryID' => $this->categoryID ?: null,
				'packageID' => PackageCache::getInstance()->getPackageID('com.woltlab.wcf.bbcode'),
				'aliases' => $this->aliases
			)
		));
		$this->objectAction->executeAction();
		$returnValues = $this->objectAction->getReturnValues();
		$smileyEditor = new SmileyEditor($returnValues['returnValues']);
		$smileyID = $returnValues['returnValues']->smileyID;
		
		if (!I18nHandler::getInstance()->isPlainValue('smileyTitle')) {
			I18nHandler::getInstance()->save('smileyTitle', 'wcf.smiley.title'.$smileyID, 'wcf.smiley', PackageCache::getInstance()->getPackageID('com.woltlab.wcf.bbcode'));
			
			// update title
			$smileyEditor->update(array(
				'title' => 'wcf.smiley.title'.$smileyID
			));
		}
		
		// reset values
		$this->smileyCode = '';
		$this->categoryID = 0;
		$this->showOrder = 0;
		
		I18nHandler::getInstance()->reset();
		
		$this->saved();
		
		// show success message
		WCF::getTPL()->assign('success', true);
	}
	
	/**
	 * @see	wcf\page\IForm::validate()
	 */
	public function validate() {
		parent::validate();
		
		// validate title
		if (!I18nHandler::getInstance()->validateValue('smileyTitle')) {
			throw new UserInputException('smileyTitle');
		}
		
		if ($this->categoryID !== 0) {
			$category = new Category($this->categoryID);
			if (!$category->categoryID) {
				throw new UserInputException('categoryID', 'notValid');
			}
		}
		
		if ($this->smileyCode === '') {
			throw new UserInputException('smileyCode');
		}
		
		// TODO: Validate uniqueness of smileyCode and aliases
	}
}