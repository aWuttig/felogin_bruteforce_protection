<?php
namespace Aoe\FeloginBruteforceProtection\Service;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Kevin Schu <kevin.schu@aoe.com>, AOE GmbH
 *  (c) 2014 André Wuttig <wuttig@portrino.de>, portrino GmbH
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * @author Kevin Schu <kevin.schu@aoe.com>
 * @author Timo Fuchs <timo.fuchs@aoe.com>
 * @author Andre Wuttig <wuttig@portrino.de>
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 * Class AuthUser
 *
 * @package Aoe\FeloginBruteforceProtection\\Service
 */
class AuthUser extends \TYPO3\CMS\Sv\AuthenticationService {

	/**
	 * @var \Aoe\FeloginBruteforceProtection\System\Configuration
	 */
    protected $configuration;

    /**
     * Object manager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Aoe\FeloginBruteforceProtection\Domain\Service\RestrictionService
     */
    protected $restrictionService;

    /**
     * @var \TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication
     */
    protected $frontendUserAuthentication;

    public function init() {

        $this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
        $this->configuration = $this->objectManager->get('Aoe\FeloginBruteforceProtection\\System\Configuration');

        $this->initTSFE($this->configuration->getRootPage(), TRUE, $GLOBALS['TSFE']);

        $this->restrictionService = $this->objectManager->get('Aoe\FeloginBruteforceProtection\\Domain\Service\RestrictionService');

        return parent::init();
    }

    /**
     * Initialize authentication service
     *
     * @param string $mode Subtype of the service which is used to call the service.
     * @param array $loginData Submitted login form data
     * @param array $authInfo Information array. Holds submitted form data etc.
     * @param object $pObj Parent object
     * @return void
     * @todo Define visibility
     */
    public function initAuth($mode, $loginData, $authInfo, $pObj) {
        $this->frontendUserAuthentication = $pObj;
    }

	/**
	 * Ensure chain breaking if client is already banned!
	 * Simulate an invalid user and stop the chain by setting the "fetchAllUsers" configuration to "FALSE";
	 *
	 * @return bool|array
	 */
	public function getUser() {
		if ($this->isProtectionEnabled() && $this->restrictionService->isClientRestricted()) {
			$GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup'][$this->frontendUserAuthentication->loginType . '_fetchAllUsers'] = FALSE;
			return array('uid' => 0);
		}
		return parent::getUser();
	}

	/**
	 * Ensure chain breaking if client is already banned!
	 *
	 * @param   mixed $userData Data of user.
	 * @return  integer     Chain result (<0: break chain; 100: use next chain service; 200: success)
	 */
	public function authUser($userData) {
		if ($this->isProtectionEnabled() && $this->restrictionService->isClientRestricted()) {
			return -1;
		}
		return 100;
	}

	/**
	 * @return bool
	 */
	public function isProtectionEnabled() {
		return $this->configuration->isEnabled();
	}

    private function initTSFE($pageUid = 1, $overrule = FALSE, $tsfe = NULL) {
        // begin
        if (!is_object($GLOBALS['TT']) || $overrule === TRUE) {
            $GLOBALS['TT'] = new \TYPO3\CMS\Core\TimeTracker\TimeTracker();
            $GLOBALS['TT']->start();
        }

        if ((!is_object($GLOBALS['TSFE']) || $overrule === TRUE) && is_int($pageUid)) {
            // builds TSFE object
            $GLOBALS['TSFE'] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\\TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController', $GLOBALS['TYPO3_CONF_VARS'], $pageUid, $type=0, $no_cache=0, $cHash='', $jumpurl='', $MP='', $RDCT='');

            if (isset($tsfe->fe_user)) {
                $GLOBALS['TSFE']->fe_user = $tsfe->fe_user;
            }

            // builds rootline
            $GLOBALS['TSFE']->sys_page = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\\TYPO3\\CMS\\Frontend\\Page\\PageRepository');
            $rootLine = $GLOBALS['TSFE']->sys_page->getRootLine($pageUid);

            // init template
            $GLOBALS['TSFE']->tmpl = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\\TYPO3\\CMS\\Core\\TypoScript\\ExtendedTemplateService');
            $GLOBALS['TSFE']->tmpl->tt_track = 0;// Do not log time-performance information
            $GLOBALS['TSFE']->tmpl->init();

            // this generates the constants/config + hierarchy info for the template.
            $GLOBALS['TSFE']->tmpl->runThroughTemplates($rootLine, $start_template_uid=0);
            $GLOBALS['TSFE']->tmpl->generateConfig();
            $GLOBALS['TSFE']->tmpl->loaded=1;

            // get config array and other init from pagegen
            $GLOBALS['TSFE']->set_no_cache();
            $GLOBALS['TSFE']->getConfigArray();

            \TYPO3\CMS\Core\Core\Bootstrap::getInstance()->loadCachedTca();

            $GLOBALS['TSFE']->cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer');
            $GLOBALS['TSFE']->settingLanguage();
            $GLOBALS['TSFE']->settingLocale();
        }
    }

}