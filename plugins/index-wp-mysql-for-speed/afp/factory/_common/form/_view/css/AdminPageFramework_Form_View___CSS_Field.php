<?php 
/**
	Admin Page Framework v3.8.34 by Michael Uno 
	Generated by PHP Class Files Script Generator <https://github.com/michaeluno/PHP-Class-Files-Script-Generator>
	<http://en.michaeluno.jp/index-wp-mysql-for-speed>
	Copyright (c) 2013-2021, Michael Uno; Licensed under MIT <http://opensource.org/licenses/MIT> */
class Imfs_AdminPageFramework_Form_View___CSS_Field extends Imfs_AdminPageFramework_Form_View___CSS_Base {
    protected function _get() {
        return $this->___getFormFieldRules();
    }
    static private function ___getFormFieldRules() {
        return "td.index-wp-mysql-for-speed-field-td-no-title {padding-left: 0;padding-right: 0;}.index-wp-mysql-for-speed-fields {display: table; width: 100%;table-layout: fixed;}.index-wp-mysql-for-speed-field input[type='number'] {text-align: right;} .index-wp-mysql-for-speed-fields .disabled,.index-wp-mysql-for-speed-fields .disabled input,.index-wp-mysql-for-speed-fields .disabled textarea,.index-wp-mysql-for-speed-fields .disabled select,.index-wp-mysql-for-speed-fields .disabled option {color: #BBB;}.index-wp-mysql-for-speed-fields hr {border: 0; height: 0;border-top: 1px solid #dfdfdf; }.index-wp-mysql-for-speed-fields .delimiter {display: inline;}.index-wp-mysql-for-speed-fields-description {margin-bottom: 0;}.index-wp-mysql-for-speed-field {float: left;clear: both;display: inline-block;margin: 1px 0;}.index-wp-mysql-for-speed-field label {display: inline-block; width: 100%;}@media screen and (max-width: 782px) {.form-table fieldset > label {display: inline-block;}}.index-wp-mysql-for-speed-field .index-wp-mysql-for-speed-input-label-container {margin-bottom: 0.25em;}@media only screen and ( max-width: 780px ) { .index-wp-mysql-for-speed-field .index-wp-mysql-for-speed-input-label-container {margin-top: 0.5em; margin-bottom: 0.5em;}} .index-wp-mysql-for-speed-field .index-wp-mysql-for-speed-input-label-string {padding-right: 1em; vertical-align: middle; display: inline-block; }.index-wp-mysql-for-speed-field .index-wp-mysql-for-speed-input-button-container {padding-right: 1em; }.index-wp-mysql-for-speed-field .index-wp-mysql-for-speed-input-container {display: inline-block;vertical-align: middle;}.index-wp-mysql-for-speed-field-image .index-wp-mysql-for-speed-input-label-container { vertical-align: middle;}.index-wp-mysql-for-speed-field .index-wp-mysql-for-speed-input-label-container {display: inline-block; vertical-align: middle; } .repeatable .index-wp-mysql-for-speed-field {clear: both;display: block;}.index-wp-mysql-for-speed-repeatable-field-buttons {float: right; margin: 0.1em 0 0.5em 0.3em;vertical-align: middle;}.index-wp-mysql-for-speed-repeatable-field-buttons .repeatable-field-button {margin: 0 0.1em;font-weight: normal;vertical-align: middle;text-align: center;}@media only screen and (max-width: 960px) {.index-wp-mysql-for-speed-repeatable-field-buttons {margin-top: 0;}}.index-wp-mysql-for-speed-sections.sortable-section > .index-wp-mysql-for-speed-section,.sortable > .index-wp-mysql-for-speed-field {clear: both;float: left;display: inline-block;padding: 1em 1.32em 1em;margin: 1px 0 0 0;border-top-width: 1px;border-bottom-width: 1px;border-bottom-style: solid;-webkit-user-select: none;-moz-user-select: none;user-select: none; text-shadow: #fff 0 1px 0;-webkit-box-shadow: 0 1px 0 #fff;box-shadow: 0 1px 0 #fff;-webkit-box-shadow: inset 0 1px 0 #fff;box-shadow: inset 0 1px 0 #fff;-webkit-border-radius: 3px;border-radius: 3px;background: #f1f1f1;background-image: -webkit-gradient(linear, left bottom, left top, from(#ececec), to(#f9f9f9));background-image: -webkit-linear-gradient(bottom, #ececec, #f9f9f9);background-image: -moz-linear-gradient(bottom, #ececec, #f9f9f9);background-image: -o-linear-gradient(bottom, #ececec, #f9f9f9);background-image: linear-gradient(to top, #ececec, #f9f9f9);border: 1px solid #CCC;background: #F6F6F6;} .index-wp-mysql-for-speed-fields.sortable {margin-bottom: 1.2em; } .index-wp-mysql-for-speed-field .button.button-small {width: auto;} .font-lighter {font-weight: lighter;} .index-wp-mysql-for-speed-field .button.button-small.dashicons {font-size: 1.2em;padding-left: 0.2em;padding-right: 0.22em;min-width: 1em; }@media screen and (max-width: 782px) {.index-wp-mysql-for-speed-field .button.button-small.dashicons {min-width: 1.8em; }}.index-wp-mysql-for-speed-field .button.button-small.dashicons:before {position: relative;top: 7.2%;}@media screen and (max-width: 782px) {.index-wp-mysql-for-speed-field .button.button-small.dashicons:before {top: 8.2%;}}.index-wp-mysql-for-speed-field-title {font-weight: 600;min-width: 80px;margin-right: 1em;}.index-wp-mysql-for-speed-fieldset {font-weight: normal;}.index-wp-mysql-for-speed-input-label-container,.index-wp-mysql-for-speed-input-label-string{min-width: 140px;}";
    }
    protected function _getVersionSpecific() {
        $_sCSSRules = '';
        if (version_compare($GLOBALS['wp_version'], '3.8', '<')) {
            $_sCSSRules.= ".index-wp-mysql-for-speed-field .remove_value.button.button-small {line-height: 1.5em; }";
        }
        $_sCSSRules.= $this->___getForWP38OrAbove();
        $_sCSSRules.= $this->___getForWP53OrAbove();
        return $_sCSSRules;
    }
    private function ___getForWP38OrAbove() {
        if (version_compare($GLOBALS['wp_version'], '3.8', '<')) {
            return '';
        }
        return ".index-wp-mysql-for-speed-repeatable-field-buttons {margin: 2px 0 0 0.3em;}.index-wp-mysql-for-speed-repeatable-field-buttons.disabled > .repeatable-field-button {color: #edd;border-color: #edd;} @media screen and ( max-width: 782px ) {.index-wp-mysql-for-speed-fieldset {overflow-x: hidden;overflow-y: hidden;}}";
    }
    private function ___getForWP53OrAbove() {
        if (version_compare($GLOBALS['wp_version'], '5.3', '<')) {
            return '';
        }
        return ".index-wp-mysql-for-speed-field .button.button-small.dashicons:before {position: relative;top: -5.4px;}@media screen and (max-width: 782px) {.index-wp-mysql-for-speed-field .button.button-small.dashicons:before {top: -6.2%;}.index-wp-mysql-for-speed-field .button.button-small.dashicons {min-width: 2.4em;}}.index-wp-mysql-for-speed-field .index-wp-mysql-for-speed-repeatable-field-buttons {display: flex;}.index-wp-mysql-for-speed-field .index-wp-mysql-for-speed-repeatable-field-buttons {}.index-wp-mysql-for-speed-field .index-wp-mysql-for-speed-repeatable-field-buttons .repeatable-field-button.button {margin: 0 0.1em;display: flex;align-items: center;justify-content: center;}.index-wp-mysql-for-speed-field .repeatable-field-button .dashicons {position: initial;top: initial;display: flex;align-items: center;justify-content: center;font-size: 16px;}.index-wp-mysql-for-speed-repeatable-field-buttons .repeatable-field-button.button.button-small {min-width: 2.4em;min-height: 2.4em;padding: 0;}.with-nested-fields .index-wp-mysql-for-speed-repeatable-field-buttons .repeatable-field-button {width: 2em;height: 2em;max-width: unset;max-height: unset;min-width: unset;min-height: unset;}@media screen and (max-width: 782px) {.index-wp-mysql-for-speed-repeatable-field-buttons {margin: 0.64em 0 0 0.28em;}.index-wp-mysql-for-speed-field .repeatable-field-button .dashicons {font-size: 20px;}.index-wp-mysql-for-speed-repeatable-field-buttons .repeatable-field-button.button.button-small {margin-top: 0;margin-bottom: 0;min-width: 2.6em;min-height: 2.6em;}.index-wp-mysql-for-speed-fields.sortable .index-wp-mysql-for-speed-repeatable-field-buttons {margin: 0.6em 0 0 1em;}.with-nested-fields .index-wp-mysql-for-speed-repeatable-field-buttons .repeatable-field-button {}.with-nested-fields .repeatable-field-button .dashicons {top: 4px;}}";
    }
    }
    