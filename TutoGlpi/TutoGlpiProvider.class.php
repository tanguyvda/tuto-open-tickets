<?php
/*
 * Copyright 2019 Centreon (http://www.centreon.com/)
 *
 * Centreon is a full-fledged industry-strength solution that meets
 * the needs in IT infrastructure and application monitoring for
 * service performance.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,*
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

class TutoGlpiProvider extends AbstractProvider {

    const GLPI_ENTITIES_TYPE = 10;

    const ARG_CONTENT = 1;
    const ARG_ENTITY = 2;
    const ARG_URGENCY = 3;
    const ARG_TITLE = 4;

    protected $_internal_arg_name = array(
        self::ARG_CONTENT => 'content',
        self::ARG_ENTITY => 'entity',
        self::ARG_URGENCY => 'urgency',
        self::ARG_TITLE => 'title'
    );

    /*
    * Set default values for our rule form options
    *
    * @return void
    */
    protected function _setDefaultValueExtra() {

        $this->default_data['address'] = '10.30.2.2';
        $this->default_data['api_path'] = '/glpi/apirest.php';
        $this->default_data['user_token'] = '';
        $this->default_data['app_token'] = '';
        $this->default_data['https'] = 0;
        $this->default_data['timeout'] = 60;

        $this->default_data['clones']['mappingTicket'] = array(
          array(
            'Arg' =>  self::ARG_TITLE,
            'Value' => 'Issue {include file="file:$_centreon_open_tickets_path/providers/Abstract/templates/display_title.ihtml"}'
          ),
          array(
            'Arg' => self::ARG_CONTENT,
            'Value' => '{$body}'
          ),
          array(
            'Arg' => self::ARG_ENTITY,
            'Value' => '{$select.glpi_entity.id}'
          ),
          array(
            'Arg' => self::ARG_URGENCY,
            'Value' => '{$select.urgency.value}'
          )
        );
    }

    protected function _setDefaultValueMain($body_html = 0) {
        parent::_setDefaultValueMain();

        $this->default_data['clones']['groupList'] = array(
            array(
                'Id' => 'glpi_entity',
                'Label' => _('Entity'),
                'Type' => self::GLPI_ENTITIES_TYPE,
                'Filter' => '',
                'Mandatory' => ''
            )
      );

    }

    /*
    * Verify if every mandatory form field is filled with data
    *
    * @return void
    * @throw Exception
    */
    protected function _checkConfigForm() {
        $this->_check_error_message = '';
        $this->_check_error_message_append = '';

        $this->_checkFormValue('address', 'Please set "Address" value');
        $this->_checkFormValue('api_path', 'Please set "API path" value');
        $this->_checkFormValue('user_token', 'Please set "User token" value');
        $this->_checkFormValue('app_token', 'Please set "APP token" value');
        // you know what ? we're going to check if the timeout is an integer too
        $this->_checkFormInteger('timeout', '"Timeout" must be an integer');

        $this->_checkLists();

        if ($this->_check_error_message != '') {
            throw new Exception($this->_check_error_message);
        }
    }

    /*
    * Initiate your html configuration and let Smarty display it in the rule form
    *
    * @return void
    */
    protected function _getConfigContainer1Extra() {
        // initiate smarty and a few variables.
        $tpl = new Smarty();
        $tpl = initSmartyTplForPopup($this->_centreon_open_tickets_path, $tpl, 'providers/TutoGlpi/templates',
        $this->_centreon_path);
        $tpl->assign('centreon_open_tickets_path', $this->_centreon_open_tickets_path);
        $tpl->assign('img_brick', './modules/centreon-open-tickets/images/brick.png');
        // Don't be afraid when you see _('Tuto Glpi'), that is just a short syntax for gettext. It is used to translate strings.
        $tpl->assign('header', array('TutoGlpi' => _("Tuto Glpi")));

        /*
        * we create the html that is going to be displayed
        */
        $address_html = '<input size="50" name="address" type="text" value="' . $this->_getFormValue('address') .'" />';
        $api_path_html = '<input size="50" name="api_path" type="text" value="' . $this->_getFormValue('api_path') . '" />';
        $user_token_html = '<input size="50" name="user_token" type="text" value="' . $this->_getFormValue('user_token') . '" autocomplete="off" />';
        $app_token_html = '<input size="50" name="app_token" type="text" value="' . $this->_getFormValue('app_token') . '" autocomplete="off" />';
        // for those who aren't familiar with ternary conditions, this means that if in the form, the value of https is equal to yes, then the input
        // will have the checked attribute, else, it won't, resulting in a ticked or unticked checkbox
        $https_html = '<input type=checkbox name="https" value="yes" ' . ($this->_getFormValue('https') == 'yes' ? 'checked' : '') . '/>';
        $timeout_html = '<input size="50" name="timeout" type="text" value="' . $this->_getFormValue('timeout') . '" :>';

        // this array is here to link a label with the html code that we've wrote above
        $array_form = array(
            'address' => array(
                'label' => _('Address') . $this->_required_field,
                'html' => $address_html
            ),
            'api_path' => array(
                'label' => _('API path') . $this->_required_field,
                'html' => $api_path_html
            ),
            'user_token' => array(
                'label' => _('User token') . $this->_required_field,
                'html' => $user_token_html
            ),
            'app_token' => array(
                'label' => _('APP token') . $this->_required_field,
                'html' => $app_token_html
            ),
            'https' => array(
                'label' => _('https'),
                'html' => $https_html
            ),
            'timeout' => array(
                'label' => _('Timeout'),
                'html' => $timeout_html
            ),
            //we add a key to our array
            'mappingTicket' => array(
                'label' => _('Mapping ticket arguments')
            )
        );

        // html
        $mappingTicketValue_html = '<input id="mappingTicketValue_#index#" name="mappingTicketValue[#index#]" size="20" type="text"';

        // html code for a dropdown list where we will be able to select something from the following list
        $mappingTicketArg_html = '<select id="mappingTicketArg_#index#" name="mappingTicketArg[#index#]" type="select-one">' .
          '<option value="' . self::ARG_TITLE . '">' . _("Title") . '</option>' .
          '<option value="' . self::ARG_CONTENT . '">' . _("Content") . '</option>' .
          '<option value="' . self::ARG_ENTITY . '">' . _("Entity") . '</option>' .
          '<option value="' . self::ARG_URGENCY . '">' . _("Urgency") . '</option>' .
        '</select>';

        // we asociate the label with the html code but for the arguments that we've been working on lately
        $array_form['mappingTicket'] = array(
          array(
            'label' => _('Argument'),
            'html' => $mappingTicketArg_html
          ),
          array(
            'label' => _('Value'),
            'html' => $mappingTicketValue_html
          )
        );

        $tpl->assign('form', $array_form);
        $this->_config['container1_html'] .= $tpl->fetch('conf_container1extra.ihtml');
        $this->_config['clones']['mappingTicket'] = $this->_getCloneValue('mappingTicket');
    }

    protected function _getConfigContainer2Extra() {

    }

    /*
    * Saves the rule form in the database
    *
    * @return void
    */
    protected function saveConfigExtra() {
        $this->_save_config['simple']['address'] = $this->_submitted_config['address'];
        $this->_save_config['simple']['api_path'] = $this->_submitted_config['api_path'];
        $this->_save_config['simple']['user_token'] = $this->_submitted_config['user_token'];
        $this->_save_config['simple']['app_token'] = $this->_submitted_config['app_token'];
        $this->_save_config['simple']['https'] = $this->_submitted_config['https'];
        $this->_save_config['simple']['timeout'] = $this->_submitted_config['timeout'];

        // saves the ticket arguments
        $this->_save_config['clones']['mappingTicket'] = $this->_getCloneSubmitted('mappingTicket', array('Arg', 'Value'));
    }

    protected function getGroupListOptions() {
        $str = '<option value="' . self::GLPI_ENTITIES_TYPE . '">Glpi entities</option>';

        return $str;

    }

    protected function assignOthers($entry, &$groups_order, &$groups) {

    }

    public function validateFormatPopup() {

    }

    protected function assignSubmittedValueSelectMore($select_input_id, $selected_id) {

    }

    protected function doSubmit($db_storage, $contact, $host_problems, $service_problems, $extra_ticket_arguments=array()) {

    }

}
