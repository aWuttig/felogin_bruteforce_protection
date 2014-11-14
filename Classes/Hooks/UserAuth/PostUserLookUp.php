<?php
namespace Aoe\FeloginBruteforceProtection\Hook\UserAuth;

/***************************************************************
 * Copyright notice
 *
 * (c) 2013 Kevin Schu <kevin.schu@aoemedia.de>, AOE media GmbH
 * (c) 2014 André Wuttig <wuttig@portrino.de>, portrino GmbH
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/



//Alias long namespaces to use shorter ones.
use \TYPO3\CMS\Core as Core;
use \TYPO3\CMS\Extbase\Utility as Utility;
use \TYPO3\CMS\Frontend as Frontend;

/**
 * Class PostUserLookUp
 *
 * @package Aoe\FeloginBruteforceProtection\\Hook\UserAuth
 *
 * @author Kevin Schu <kevin.schu@aoemedia.de>
 * @author Timo Fuchs <timo.fuchs@aoemedia.de>
 * @author Andre Wuttig <wuttig@portrino.de>
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class PostUserLookUp {

    /**
     * Object manager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Aoe\FeloginBruteforceProtection\System\Configuration
     */
    protected $configuration;

    /**
     * @var \Aoe\FeloginBruteforceProtection\Domain\Service\RestrictionService
     */
    protected $restrictionService;

	/**
	 * @param array $params
	 * @return void
	 */
	public function handlePostUserLookUp(&$params) {

        $this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
        $this->configuration = $this->objectManager->get('Aoe\FeloginBruteforceProtection\\System\Configuration');

        $this->initTSFE($this->configuration->getRootPage(), TRUE, $GLOBALS['TSFE']);

        $this->restrictionService = $this->objectManager->get('Aoe\FeloginBruteforceProtection\\Domain\Service\RestrictionService');

        if(FALSE === $this->configuration->isEnabled()) {
			return;
		}

        /** @var \TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication $frontendUserAuthentication */
		$frontendUserAuthentication = $params['pObj'];

		if ($this->hasFeUserLoggedIn($frontendUserAuthentication)) {
			$this->restrictionService->removeEntry();
		} elseif($this->hasFeUserLogInFailed($frontendUserAuthentication)) {
			$this->restrictionService->incrementFailureCount();
			$this->updateGlobals($frontendUserAuthentication);
		}
	}

	/**
	 * @param $userAuthObject
	 * @return boolean
	 */
	private function updateGlobals(&$userAuthObject) {
		$GLOBALS ['felogin_bruteforce_protection'] ['restricted'] = FALSE;
		if ($this->restrictionService->isClientRestricted ()) {
			$userAuthObject->loginFailure = 1;
			$GLOBALS ['felogin_bruteforce_protection'] ['restricted'] = TRUE;
			$GLOBALS ['felogin_bruteforce_protection'] ['restriction_time'] = $this->configuration->getRestrictionTime ();
			$GLOBALS ['felogin_bruteforce_protection'] ['restriction_message'] = $this->getRestrictionMessage ();
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * @return string
	 */
	private function getRestrictionMessage() {
		$time = (integer) ($this->configuration->getRestrictionTime () / 60);
		return \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('restriction_message', 'felogin_bruteforce_protection', array ($time, $time));
	}

	/**
	 * check, if FE-user has logged in in this request
	 *
	 * @param \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication $userAuthObject
	 */
	private function hasFeUserLoggedIn(\TYPO3\CMS\Core\Authentication\AbstractUserAuthentication $userAuthObject) {
		if ($userAuthObject->loginType === 'FE' && $userAuthObject->loginFailure === FALSE && is_array($userAuthObject->user) && $userAuthObject->loginSessionStarted === TRUE) {
            return TRUE;
		}
		return FALSE;
	}

	/**
	 * check, if login-action of FE-user failed
	 *
	 * @param \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication $userAuthObject
	 */
	private function hasFeUserLogInFailed(\TYPO3\CMS\Core\Authentication\AbstractUserAuthentication $userAuthObject) {
        if ($userAuthObject->loginType === 'FE' && $userAuthObject->loginFailure === TRUE && !$userAuthObject->user) {
			return TRUE;
		}
		return FALSE;
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
            // will throw warnings, at this point we do not know why
//            $GLOBALS['TSFE']->settingLanguage();
            $GLOBALS['TSFE']->settingLocale();
        }
    }

}