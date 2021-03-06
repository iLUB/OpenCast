<?php
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.xoctWaiterGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Conf/class.xoctConf.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/IVTGroup/class.xoctUser.php');

/**
 * Class xoctSeriesFormGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class xoctSeriesFormGUI extends ilPropertyFormGUI {

	const F_COURSE_NAME = 'course_name';
	const F_TITLE = 'title';
	const F_DESCRIPTION = 'description';
	const F_CHANNEL_TYPE = 'channel_type';
	const EXISTING_NO = 1;
	const EXISTING_YES = 2;
	const F_INTRODUCTION_TEXT = 'introduction_text';
	const F_INTENDED_LIFETIME = 'intended_lifetime';
	const F_EST_VIDEO_LENGTH = 'est_video_length';
	const F_LICENSE = 'license';
	const F_DISCIPLINE = 'discipline';
	const F_DEPARTMENT = 'department';
	const F_STREAMING_ONLY = 'streaming_only';
	const F_USE_ANNOTATIONS = 'use_annotations';
	const F_PERMISSION_PER_CLIP = 'permission_per_clip';
	const F_ACCEPT_EULA = 'accept_eula';
	const F_EXISTING_IDENTIFIER = 'existing_identifier';
	const F_PERMISSION_ALLOW_SET_OWN = 'permission_allow_set_own';
	const F_OBJ_ONLINE = 'obj_online';
	const F_CHANNEL_ID = 'channel_id';
	const F_MEMBER_UPLOAD = 'member_upload';
	const F_SHOW_UPLOAD_TOKEN = 'show_upload_token';
	/**
	 * @var  xoctSeries
	 */
	protected $object;
	/**
	 * @var xoctSeriesGUI
	 */
	protected $parent_gui;
	/**
	 * @var  ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var ilOpenCastPlugin
	 */
	protected $pl;
	/**
	 * @var bool
	 */
	protected $external = true;


	/**
	 * @param              $parent_gui
	 * @param xoctOpenCast $cast
	 * @param bool $view
	 * @param bool $infopage
	 * @param bool $external
	 */
	public function __construct($parent_gui, xoctOpenCast $cast, $view = false, $infopage = false, $external = true) {
		global $ilCtrl, $lng, $tpl;
		$this->cast = $cast;
		$this->series = $cast->getSeries();
		$this->parent_gui = $parent_gui;
		$this->ctrl = $ilCtrl;
		$this->pl = ilOpenCastPlugin::getInstance();
		$this->ctrl->saveParameter($parent_gui, xoctSeriesGUI::SERIES_ID);
		$this->ctrl->saveParameter($parent_gui, 'new_type');
		$this->lng = $lng;
		$this->is_new = ($this->series->getIdentifier() == '');
		$this->view = $view;
		$this->infopage = $infopage;
		$this->external = $external;
		xoctWaiterGUI::loadLib();
		$tpl->addJavaScript($this->pl->getStyleSheetLocation('default/existing_channel.js'));
		if ($view) {
			$this->initView();
		} else {
			$this->initForm();
		}
	}


	protected function initForm() {
		global $ilUser;
		$xoctUser = xoctUser::getInstance($ilUser);
		$this->setTarget('_top');
		$this->setFormAction($this->ctrl->getFormAction($this->parent_gui));
		$this->initButtons();
		if ($this->is_new) {
			$existing_channel = new ilRadioGroupInputGUI($this->txt(self::F_CHANNEL_TYPE), self::F_CHANNEL_TYPE);
			{
				$existing = new ilRadioOption($this->txt('existing_channel_yes'), self::EXISTING_YES);
				{
					$existing_identifier = new ilSelectInputGUI($this->txt(self::F_EXISTING_IDENTIFIER), self::F_EXISTING_IDENTIFIER);
					require_once('class.xoctSeries.php');
					$existing_series = array();
					foreach (xoctSeries::getAllForUser($xoctUser->getUserRoleName()) as $serie) {
						$existing_series[$serie->getIdentifier()] = $serie->getTitle() . ' (...' . substr($serie->getIdentifier(), - 4, 4) . ')';
					}
					array_multisort($existing_series);
					$existing_identifier->setOptions($existing_series);
					$existing->addSubItem($existing_identifier);
				}
				$existing_channel->addOption($existing);

				$new = new ilRadioOption($this->txt('existing_channel_no'), self::EXISTING_NO);
				$existing_channel->addOption($new);
			}

			$this->addItem($existing_channel);
		}

		$te = new ilTextInputGUI($this->txt(self::F_TITLE), self::F_TITLE);
		$te->setRequired(true);
		$this->addItem($te);

		$te = new ilTextAreaInputGUI($this->txt(self::F_DESCRIPTION), self::F_DESCRIPTION);
		$this->addItem($te);

		$te = new ilCheckboxInputGUI($this->txt(self::F_OBJ_ONLINE), self::F_OBJ_ONLINE);
		$this->addItem($te);

		$te = new ilTextAreaInputGUI($this->txt(self::F_INTRODUCTION_TEXT), self::F_INTRODUCTION_TEXT);
		$te->setUseRte(true);
		$te->setRteTags(array( 'p', 'a', 'br', 'b', 'i', 'strong', 'emp', 'imp', 'em', 'span', 'u', 'sub', 'sup' ));
		$te->usePurifier(false);
		$te->disableButtons(array(
			'charmap',
			'undo',
			'redo',
			'justifyleft',
			'justifycenter',
			'justifyright',
			'justifyfull',
			'anchor',
			'fullscreen',
			'cut',
			'copy',
			'paste',
			'pastetext',
			'formatselect',
		));

		$te->setRows(5);
		$this->addItem($te);

		//		$discipline = new ilSelectInputGUI($this->txt(self::F_DISCIPLINE), self::F_DISCIPLINE);
		//		sort(self::$disciplines);
		//		$discipline->setOptions(self::$disciplines);
		//		$discipline->setRequired(true);
		//		$this->addItem($discipline);

		$license = new ilSelectInputGUI($this->txt(self::F_LICENSE), self::F_LICENSE);
		$options = array(
			null => 'As defined in content',
		);
		$licenses = xoctConf::getConfig(xoctConf::F_LICENSES);
		$license_info = xoctConf::getConfig(xoctConf::F_LICENSE_INFO);
		if ($licenses) {
			foreach (explode("\n", $licenses) as $nl) {
				$lic = explode("#", $nl);
				if ($lic[0] && $lic[1]) {
					$options[$lic[0]] = $lic[1];
				}
			}
		}
		$license->setInfo($license_info);
		$license->setOptions($options);
		$this->addItem($license);

		$department = new ilTextInputGUI($this->txt(self::F_DEPARTMENT), self::F_DEPARTMENT);
		$department->setInfo($this->infoTxt(self::F_DEPARTMENT));
		// $this->addItem($department);

		$use_annotations = new ilCheckboxInputGUI($this->txt(self::F_USE_ANNOTATIONS), self::F_USE_ANNOTATIONS);
		$this->addItem($use_annotations);

		$streaming_only = new ilCheckboxInputGUI($this->txt(self::F_STREAMING_ONLY), self::F_STREAMING_ONLY);
		$this->addItem($streaming_only);

		$permission_per_clip = new ilCheckboxInputGUI($this->txt(self::F_PERMISSION_PER_CLIP), self::F_PERMISSION_PER_CLIP);
		$permission_per_clip->setInfo($this->infoTxt(self::F_PERMISSION_PER_CLIP));

		$set_own_rights = new ilCheckboxInputGUI($this->txt(self::F_PERMISSION_ALLOW_SET_OWN), self::F_PERMISSION_ALLOW_SET_OWN);
		$set_own_rights->setInfo($this->infoTxt(self::F_PERMISSION_ALLOW_SET_OWN));
		$permission_per_clip->addSubItem($set_own_rights);

		$this->addItem($permission_per_clip);

		if ($this->is_new && ilObjOpenCast::_getParentCourseOrGroup($_GET['ref_id'])) {
			$crs_member_upload = new ilCheckboxInputGUI($this->txt(self::F_MEMBER_UPLOAD), self::F_MEMBER_UPLOAD);
			$crs_member_upload->setInfo($this->infoTxt(self::F_MEMBER_UPLOAD));
			$this->addItem($crs_member_upload);
		}

		if ($this->is_new) {
			$accept_eula = new ilCheckboxInputGUI($this->txt(self::F_ACCEPT_EULA), self::F_ACCEPT_EULA);
			$accept_eula->setInfo(xoctConf::getConfig(xoctConf::F_EULA));
			$accept_eula->setRequired(true);
			$this->addItem($accept_eula);
		}

		if (!$this->is_new) {
			$channel_id = new ilNonEditableValueGUI($this->txt(self::F_CHANNEL_ID), self::F_CHANNEL_ID);
			$this->addItem($channel_id);
		}
	}


	public function fillFormRandomized() {
		$array = array(
			self::F_CHANNEL_TYPE             => self::EXISTING_NO,
			self::F_TITLE                    => 'New Channel ' . date(DATE_ATOM),
			self::F_DESCRIPTION              => 'This is a description',
			self::F_INTRODUCTION_TEXT        => 'We don\'t need no intro text',
			self::F_LICENSE                  => $this->series->getLicense(),
			self::F_USE_ANNOTATIONS          => true,
			self::F_STREAMING_ONLY           => true,
			self::F_PERMISSION_PER_CLIP      => true,
			self::F_PERMISSION_ALLOW_SET_OWN => true,
			self::F_ACCEPT_EULA              => true,
		);

		$this->setValuesByArray($array);
	}


	public function fillForm() {
		$array = array(
			self::F_CHANNEL_TYPE             => self::EXISTING_NO,
			self::F_TITLE                    => $this->series->getTitle(),
			self::F_DESCRIPTION              => $this->series->getDescription(),
			self::F_INTRODUCTION_TEXT        => $this->cast->getIntroText(),
			self::F_LICENSE                  => $this->series->getLicense(),
			self::F_USE_ANNOTATIONS          => $this->cast->getUseAnnotations(),
			self::F_STREAMING_ONLY           => $this->cast->getStreamingOnly(),
			self::F_PERMISSION_PER_CLIP      => $this->cast->getPermissionPerClip(),
			self::F_PERMISSION_ALLOW_SET_OWN => $this->cast->getPermissionAllowSetOwn(),
			self::F_OBJ_ONLINE               => $this->cast->isObjOnline(),
			self::F_CHANNEL_ID               => $this->cast->getSeriesIdentifier(),
		);

		$this->setValuesByArray($array);
	}


	/**
	 * returns whether checkinput was successful or not.
	 *
	 * @return bool
	 */
	public function fillObject() {
		if (!$this->checkInput()) {
			$this->checkEula();

			return false;
		}
		if (!$this->checkEula()) {
			return false;
		}

		if ($this->getInput(self::F_CHANNEL_TYPE) == self::EXISTING_YES) {
			$this->series->setIdentifier($this->getInput(self::F_EXISTING_IDENTIFIER));
		}
		$this->series->setTitle($this->getInput(self::F_TITLE));
		$this->series->setDescription($this->getInput(self::F_DESCRIPTION));
		$this->series->setLicense($this->getInput(self::F_LICENSE));

		$this->cast->setIntroText($this->getInput(self::F_INTRODUCTION_TEXT));
		$this->cast->setUseAnnotations($this->getInput(self::F_USE_ANNOTATIONS));
		$this->cast->setStreamingOnly($this->getInput(self::F_STREAMING_ONLY));
		$this->cast->setPermissionPerClip($this->getInput(self::F_PERMISSION_PER_CLIP));
		$this->cast->setPermissionAllowSetOwn($this->getInput(self::F_PERMISSION_ALLOW_SET_OWN));
		$this->cast->setObjOnline($this->getInput(self::F_OBJ_ONLINE));
		$this->cast->setAgreementAccepted(true);

		return true;
	}


	/**
	 * @param $key
	 *
	 * @return string
	 */
	protected function txt($key) {
		return $this->pl->txt('series_' . $key);
	}


	/**
	 * @param $key
	 *
	 * @return string
	 */
	protected function infoTxt($key) {
		return $this->pl->txt('series_' . $key . '_info');
	}


	/**
	 * @return bool|string
	 */
	public function saveObject($obj_id = null) {
		$ivt_mode_before_update = $this->cast->getPermissionPerClip();
		if (!$this->fillObject()) {
			return false;
		}

		// show / disable owner field if ivt mode has changed
		$ivt_mode_after_update = $this->cast->getPermissionPerClip();
		if ($ivt_mode_after_update != $ivt_mode_before_update &&
			($ivt_mode_after_update == 1 || !ilObjOpenCastAccess::isActionAllowedForRole('upload', 'member'))) {
			xoctEventTableGUI::setOwnerFieldVisibility($ivt_mode_after_update, $this->cast);
		}

		if ($obj_id) {
			$this->cast->setObjId($obj_id);
		}

		if ($this->series->getIdentifier()) {
			$this->cast->setSeriesIdentifier($this->series->getIdentifier());
			$this->series->update();
			if ($this->is_new) {
				$this->cast->create(); //TODO check if unnecessary, since the cast will be created later in afterSave
			} else {
				$this->cast->update();
			}
		} else {
			$this->series->create();
			$this->cast->setSeriesIdentifier($this->series->getIdentifier());
		}

		return array($this->cast, $this->getInput(self::F_MEMBER_UPLOAD));
	}


	protected function initButtons() {
		if ($this->is_new) {
			$this->setTitle($this->txt('create'));
			$this->addCommandButton(xoctSeriesGUI::CMD_CREATE, $this->txt(xoctSeriesGUI::CMD_CREATE));
		} else {
			$this->setTitle($this->txt('edit'));
			$this->addCommandButton(xoctSeriesGUI::CMD_UPDATE, $this->txt(xoctSeriesGUI::CMD_UPDATE));
		}

		$this->addCommandButton(xoctSeriesGUI::CMD_CANCEL, $this->txt(xoctSeriesGUI::CMD_CANCEL));
	}


	/**
	 * @return xoctSeriesFormGUI
	 */
	public function getAsPropertyFormGui() {
		$ilPropertyFormGUI = $this;
		$ilPropertyFormGUI->clearCommandButtons();
		$ilPropertyFormGUI->addCommandButton(xoctSeriesGUI::CMD_SAVE, $this->lng->txt(xoctSeriesGUI::CMD_SAVE));
		$ilPropertyFormGUI->addCommandButton(xoctSeriesGUI::CMD_CANCEL, $this->lng->txt(xoctSeriesGUI::CMD_CANCEL));

		return $ilPropertyFormGUI;
	}


	protected function initView() {
		$this->initForm();
		/**
		 * @var $item ilNonEditableValueGUI
		 */
		foreach ($this->getItems() as $item) {
			$te = new ilNonEditableValueGUI($this->txt($item->getPostVar()), $item->getPostVar());
			$this->removeItemByPostVar($item->getPostVar());
			$this->addItem($te);
		}
	}


	/**
	 * @var xoctSeries
	 */
	protected $series;


	/**
	 * @return xoctSeries
	 */
	public function getSeries() {
		return $this->series;
	}


	/**
	 * @param xoctSeries $series
	 */
	public function setSeries($series) {
		$this->series = $series;
	}


	protected static $disciplines = array(
		1932 => 'Arts & Culture',
		5314 => 'Architecture',
		6302 => 'Landscape architecture',
		5575 => 'Spatial planning',
		9202 => 'Art history',
		3119 => 'Design',
		6095 => 'Industrial design',
		5103 => 'Visual communication',
		5395 => 'Film',
		8202 => 'Music',
		2043 => 'Music education',
		9610 => 'School and church music',
		3829 => 'Theatre',
		1497 => 'Visual arts',
		6950 => 'Business',
		1676 => 'Business Administration',
		4949 => 'Business Informatics',
		7290 => 'Economics',
		2108 => 'Facility Management',
		7641 => 'Hotel business',
		6238 => 'Tourism',
		5214 => 'Education',
		1672 => 'Logopedics',
		1406 => 'Pedagogy',
		3822 => 'Orthopedagogy',
		2150 => 'Special education',
		9955 => 'Teacher education',
		6409 => 'Primary school',
		7008 => 'Secondary school I',
		4233 => 'Secondary school II',
		8220 => 'Health',
		2075 => 'Dentistry',
		5955 => 'Human medicine',
		5516 => 'Nursing',
		3424 => 'Pharmacy',
		4864 => 'Therapy',
		6688 => 'Occupational therapy',
		7072 => 'Physiotherapy',
		3787 => 'Veterinary medicine',
		4832 => 'Humanities',
		1438 => 'Archeology',
		8796 => 'History',
		7210 => 'Linguistics & Literature (LL)',
		9557 => 'Classical European languages',
		9391 => 'English LL',
		9472 => 'French LL',
		4391 => 'German LL',
		3468 => 'Italian LL',
		7408 => 'Linguistics',
		6230 => 'Other modern European languages',
		5676 => 'Other non-European languages',
		5424 => 'Rhaeto-Romanic LL',
		7599 => 'Translation studies',
		7258 => 'Musicology',
		4761 => 'Philosophy',
		3867 => 'Theology',
		6527 => 'General theology',
		5633 => 'Protestant theology',
		9787 => 'Roman catholic theology',
		5889 => 'Interdisciplinary & Other',
		6059 => 'Information & documentation',
		5561 => 'Military sciences',
		8683 => 'Sport',
		1861 => 'Law',
		4890 => 'Business law',
		2990 => 'Natural sciences & Mathematics',
		8990 => 'Astronomy',
		4195 => 'Biology',
		7793 => 'Ecology',
		6451 => 'Chemistry',
		1266 => 'Computer science',
		5255 => 'Earth Sciences',
		7950 => 'Geography',
		2158 => 'Mathematics',
		6986 => 'Physics',
		8637 => 'Social sciences',
		9619 => 'Communication and media studies',
		8367 => 'Ethnology',
		1774 => 'Gender studies',
		1514 => 'Political science',
		6005 => 'Psychology',
		7288 => 'Social work',
		6525 => 'Sociology',
		9321 => 'Technology & Applied sciences',
		3624 => 'Agriculture',
		1442 => 'Enology',
		1892 => 'Biotechnology',
		7132 => 'Building Engineering',
		5727 => 'Chemical Engineering',
		9389 => 'Construction Science',
		2527 => 'Civil Engineering',
		9738 => 'Rural Engineering and Surveying',
		5742 => 'Electrical Engineering',
		2850 => 'Environmental Engineering',
		9768 => 'Food technology',
		2979 => 'Forestry',
		1566 => 'Material sciences',
		8189 => 'Mechanical Engineering',
		5324 => 'Automoive Engineering',
		8502 => 'Microtechnology',
		4380 => 'Production and Enterprise',
		7303 => 'Telecommunication',
	);


	/**
	 * @return bool
	 */
	protected function checkEula() {
		if ($this->is_new && !$this->getInput(self::F_ACCEPT_EULA)) {
			/**
			 * @var $field ilCheckboxInputGUI
			 */
			$field = $this->getItemByPostVar(self::F_ACCEPT_EULA);
			$field->setAlert($this->txt('alert_eula'));

			ilUtil::sendFailure($this->lng->txt("form_input_not_valid"));

			return false;
		}

		return true;
	}
}

?>
