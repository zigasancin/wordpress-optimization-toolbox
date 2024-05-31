<?php 
/**
	Admin Page Framework v3.8.34 by Michael Uno 
	Generated by PHP Class Files Script Generator <https://github.com/michaeluno/PHP-Class-Files-Script-Generator>
	<http://en.michaeluno.jp/index-wp-mysql-for-speed>
	Copyright (c) 2013-2021, Michael Uno; Licensed under MIT <http://opensource.org/licenses/MIT> */
class Imfs_AdminPageFramework_Model__FormSubmission__Validator__ContactForm extends Imfs_AdminPageFramework_Model__FormSubmission__Validator_Base {
    public $sActionHookPrefix = 'try_validation_after_';
    public $iHookPriority = 10;
    public $iCallbackParameters = 5;
    public function _replyToCallback($aInputs, $aRawInputs, array $aSubmits, $aSubmitInformation, $oFactory) {
        if (!$this->_shouldProceed($oFactory, $aSubmits)) {
            return;
        }
        $this->___sendEmail($aInputs, $this->getElement($aSubmitInformation, 'input_name'), $this->getElement($aSubmitInformation, 'section_id'));
        $this->oFactory->oProp->_bDisableSavingOptions = true;
        $this->deleteTransient('apf_tfd' . md5('temporary_form_data_' . $this->oFactory->oProp->sClassName . get_current_user_id()));
        add_action("setting_update_url_{$this->oFactory->oProp->sClassName}", array($this, '_replyToRemoveConfirmationQueryKey'));
        $_oException = new Imfs_AdminPageFramework_Exception('aReturn');
        $_oException->setMeta( $aInputs );
        throw $_oException;
    }
    protected function _shouldProceed($oFactory, $aSubmits) {
        if ($oFactory->hasFieldError()) {
            return false;
        }
        return ( bool )$this->_getPressedSubmitButtonData($aSubmits, 'confirmed_sending_email');
    }
    private function ___sendEmail($aInputs, $sPressedInputNameFlat, $sSubmitSectionID) {
        $_sTransientKey = 'apf_em_' . md5($sPressedInputNameFlat . get_current_user_id());
        $_aEmailOptions = $this->getTransient($_sTransientKey);
        $this->deleteTransient($_sTransientKey);
        $_aEmailOptions = $this->getAsArray($_aEmailOptions) + array('nonce' => '', 'to' => '', 'subject' => '', 'message' => '', 'headers' => '', 'attachments' => '', 'is_html' => false, 'from' => '', 'name' => '',);
        if (false === wp_verify_nonce($_aEmailOptions['nonce'], 'apf_email_nonce_' . md5(( string )site_url()))) {
            $this->oFactory->setSettingNotice($this->oFactory->oMsg->get('nonce_verification_failed'), 'error');
            return;
        }
        $_oEmail = new Imfs_AdminPageFramework_FormEmail($_aEmailOptions, $aInputs, $sSubmitSectionID);
        $_bSent = $_oEmail->send();
        $this->oFactory->setSettingNotice($this->oFactory->oMsg->get($this->getAOrB($_bSent, 'email_sent', 'email_could_not_send')), $this->getAOrB($_bSent, 'updated', 'error'));
    }
    public function _replyToRemoveConfirmationQueryKey($sSettingUpdateURL) {
        return remove_query_arg(array('confirmation',), $sSettingUpdateURL);
    }
    }
    class Imfs_AdminPageFramework_Model__FormSubmission__Validator__ContactFormConfirm extends Imfs_AdminPageFramework_Model__FormSubmission__Validator__ContactForm {
        public $sActionHookPrefix = 'try_validation_after_';
        public $iHookPriority = 40;
        public $iCallbackParameters = 5;
        public function _replyToCallback($aInputs, $aRawInputs, array $aSubmits, $aSubmitInformation, $oFactory) {
            if (!$this->_shouldProceed($oFactory, $aSubmits)) {
                return;
            }
            $this->oFactory->setLastInputs($aInputs);
            $this->oFactory->oProp->_bDisableSavingOptions = true;
            add_filter("options_update_status_{$this->oFactory->oProp->sClassName}", array($this, '_replyToSetStatus'));
            $_oException = new Imfs_AdminPageFramework_Exception('aReturn');
            $_oException->setMeta( $this->_confirmSubmitButtonAction($this->getElement($aSubmitInformation, 'input_name'), $this->getElement($aSubmitInformation, 'section_id'), 'email') );
            throw $_oException;
        }
        protected function _shouldProceed($oFactory, $aSubmits) {
            if ($oFactory->hasFieldError()) {
                return false;
            }
            return ( bool )$this->_getPressedSubmitButtonData($aSubmits, 'confirming_sending_email');
        }
        public function _replyToSetStatus($aStatus) {
            return array('confirmation' => 'email') + $aStatus;
        }
    }
