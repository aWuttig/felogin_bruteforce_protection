<?php
namespace Aoe\FeloginBruteforceProtection\Hook\FeLogin;

/***************************************************************
*  Copyright notice
*
*  © 2014 Andre Wuttig(wuttig@portrino.de), portrino GmbH
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
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
 * Class PostProcContent
 *
 * @package Aoe\FeloginBruteforceProtection\\Hook\FeLogin
 *
 * @author Andre Wuttig <wuttig@portrino.de>
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class PostProcContent {

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
     * Adds hook for processing of extra item markers / special; for EXT felogin
     *
     * @param	array	&$params: $_params = array( 'content' => $content );
     * @param	object	&$pObj: $this
     *
     * @return	string	return: felogin module content
     */
    public function handlePostProcContent(&$params, &$pObj) {
        $this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
        $this->configuration = $this->objectManager->get('Aoe\FeloginBruteforceProtection\\System\Configuration');
        $this->restrictionService = $this->objectManager->get('Aoe\FeloginBruteforceProtection\\Domain\Service\RestrictionService');

        if(FALSE === $this->configuration->isEnabled()) {
            return;
        }

        if ($this->restrictionService->isClientRestricted ()) {

            $message = ($GLOBALS ['felogin_bruteforce_protection'] ['restriction_message']) ? $GLOBALS ['felogin_bruteforce_protection'] ['restriction_message'] : $this->getRestrictionMessage();

            $params['content'] = '
            <div class="typo3-messages">
                <div class="typo3-message message-error">
                    ' .  $message . '
                </div>
            </div>';
        }

        return $params['content'];
    }

    /**
     * @return string
     */
    private function getRestrictionMessage() {
        $time = (integer) ($this->configuration->getRestrictionTime () / 60);
        return \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('restriction_message', 'felogin_bruteforce_protection', array ($time, $time));
    }

}
?>
