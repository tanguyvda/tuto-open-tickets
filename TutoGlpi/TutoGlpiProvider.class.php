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
    protected $_close_advanced = 1;
    protected $_proxy_enabled = 1;

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
    * @return {void}
    */
    protected function _setDefaultValueExtra() {

        $this->default_data['address'] = '10.30.2.46';
        $this->default_data['api_path'] = '/glpi/apirest.php';
        $this->default_data['protocol'] = 'http';
        $this->default_data['user_token'] = '';
        $this->default_data['app_token'] = '';
        $this->default_data['https'] = 0;
        $this->default_data['timeout'] = 60;

        $this->default_data['clones']['mappingTicket'] = array(
          array(
            'Arg' =>  self::ARG_TITLE,
            'Value' => 'Issue {include file="file:$centreon_open_tickets_path/providers/Abstract/templates/display_title.ihtml"}'
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

    /*
    * Set default values for the widget popup when opening a ticket
    *
    * @return {void}
    */
    protected function _setDefaultValueMain($body_html = 0) {
        parent::_setDefaultValueMain($body_html);

        $this->default_data['url'] = '{$protocol}://{$address}{$api_path}';

        $this->default_data['clones']['groupList'] = array(
            array(
                'Id' => 'glpi_entity',
                'Label' => _('Entity'),
                'Type' => self::GLPI_ENTITIES_TYPE,
                'Filter' => '',
                'Mandatory' => ''
            ),
            array (
                'Id' => 'urgency',
                'Label' => _('Urgency'),
                'Type' => self::CUSTOM_TYPE,
                'Filter' => '',
                'Mandatory' => ''
            )
        );
        $this->default_data['clones']['customList'] = array(
            array(
                'Id' => 'urgency',
                'Value' => '5',
                'Label' => 'Very High',
                'Default' => ''
            ),
            array(
                'Id' => 'urgency',
                'Value' => '4',
                'Label' => 'High',
                'Default' => ''
            ),
            array(
                'Id' => 'urgency',
                'Value' => '3',
                'Label' => 'Medium',
                'Default' => ''
            ),
            array(
                'Id' => 'urgency',
                'Value' => '2',
                'Label' => 'Low',
                'Default' => ''
            ),
            array(
                'Id' => 'urgency',
                'Value' => '1',
                'Label' => 'Very Low',
                'Default' => ''
            ),
        );
    }

    /*
    * Verify if every mandatory form field is filled with data
    *
    * @return {void}
    *
    * @throw \Exception when a form field is not set
    */
    protected function _checkConfigForm() {
        $this->_check_error_message = '';
        $this->_check_error_message_append = '';

        $this->_checkFormValue('address', 'Please set "Address" value');
        $this->_checkFormValue('api_path', 'Please set "API path" value');
        $this->_checkFormValue('protocol', 'Please set "Protocol" value');
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
    * @return {void}
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
        $tpl->assign('webServiceUrl', './api/internal.php');

        /*
        * we create the html that is going to be displayed
        */
        $address_html = '<input size="50" name="address" type="text" value="' . $this->_getFormValue('address') .'" />';
        $api_path_html = '<input size="50" name="api_path" type="text" value="' . $this->_getFormValue('api_path') . '" />';
        $protocol_html = '<input size="50" name="protocol" type="text" value="' . $this->_getFormValue('protocol') . '" />';
        $user_token_html = '<input size="50" name="user_token" type="text" value="' . $this->_getFormValue('user_token') . '" autocomplete="off" />';
        $app_token_html = '<input size="50" name="app_token" type="text" value="' . $this->_getFormValue('app_token') . '" autocomplete="off" />';
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
            'protocol' => array(
                'label' => _('Protocol') . $this->_required_field,
                'html' => $protocol_html
            ),
            'user_token' => array(
                'label' => _('User token') . $this->_required_field,
                'html' => $user_token_html
            ),
            'app_token' => array(
                'label' => _('APP token') . $this->_required_field,
                'html' => $app_token_html
            ),
            'timeout' => array(
                'label' => _('Timeout'),
                'html' => $timeout_html
            ),
            //we add a key to our array
            'mappingTicketLabel' => array(
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
    * @return {void}
    */
    protected function saveConfigExtra() {
        $this->_save_config['simple']['address'] = $this->_submitted_config['address'];
        $this->_save_config['simple']['api_path'] = $this->_submitted_config['api_path'];
        $this->_save_config['simple']['protocol'] = $this->_submitted_config['protocol'];
        $this->_save_config['simple']['user_token'] = $this->_submitted_config['user_token'];
        $this->_save_config['simple']['app_token'] = $this->_submitted_config['app_token'];
        $this->_save_config['simple']['timeout'] = $this->_submitted_config['timeout'];

        // saves the ticket arguments
        $this->_save_config['clones']['mappingTicket'] = $this->_getCloneSubmitted('mappingTicket', array('Arg', 'Value'));
    }

    /*
    * Adds new types to the list of types
    *
    * @return {string} $str html code that add an option to a select
    */
    protected function getGroupListOptions() {
        $str = '<option value="' . self::GLPI_ENTITIES_TYPE . '">Glpi entities</option>';

        return $str;

    }

    /*
    * configure variables with the data provided by the glpi api
    *
    * @param {array} $entry ticket argument configuration information
    * @param {array} $groups_order order of the ticket arguments
    * @param {array} $groups store the data gathered from glpi
    *
    * @return {void}
    */
    protected function assignOthers($entry, &$groups_order, &$groups) {

        if ($entry['Type'] == self::GLPI_ENTITIES_TYPE) {
            $this->assignGlpiEntities($entry, $groups_order, $groups);
        }
    }

    /*
    * handle gathered entities
    *
    * @param {array} $entry ticket argument configuration information
    * @param {array} $groups_order order of the ticket arguments
    * @param {array} $groups store the data gathered from glpi
    *
    * @return {void}
    *
    * throw \Exception if we can't get entities from glpi
    */
    protected function assignGlpiEntities($entry, &$groups_order, &$groups) {
        // add a label to our entry and activate sorting or not.
        $groups[$entry['Id']] = array(
            'label' => _($entry['Label']) .
            (isset($entry['Mandatory']) && $entry['Mandatory'] == 1 ? $this->_required_field : '' ),
            'sort' => (isset($entry['Sort']) && $entry['Sort'] == 1 ? 1 : 0)
        );
        // adds our entry in the group order array
        $groups_order[] = $entry['Id'];

        // try to get entities
        try {
            $listEntities = $this->getCache($entry['Id']);
            if (is_null($listEntities)) {
                // if no entity found in cache, get them from glpi and put them in cache for 8 hours
                $listEntities = $this->getEntities();
                $this->setCache($entry['Id'], $listEntities, 8 * 3600);
            }
        } catch (\Exception $e) {
            $groups[$entry['Id']]['code'] = -1;
            $groups[$entry['Id']]['msg_error'] = $e->getMessage();
        }
        $result = array();
        /* this is what is inside $this->glpiCallResult['response'] or $listEntities at this point
        { "myentities": [
            {
              "id": 1,
              "name": "Root entity > Amazing Platform"
            },
            {
              "id": 2,
              "name": "Root entity > Centreon"
            },
            {
              "id": 3,
              "name": "Root entity > Cluster"
            },
            {
              "id": 4,
              "name": "Root entity > Databases"
            },
            {
              "id": 0,
              "name": "Root entity"
            }
          ]
        }
        */
        foreach ($listEntities['myentities'] as $entity) {
            // foreach entity found, if we don't have any filter configured, we just put the id and the name of the entity
            // inside the result array
            if (!isset($entry['Filter']) || is_null($entry['Filter']) || $entry['Filter'] == '') {
                $result[$entity['id']] = $this->to_utf8($entity['name']);
                continue;
            }

            // if we do have have a filter, we make sure that the match the filter, if so, we put the name and the id
            // of the entity inside the result array
            if (preg_match('/' . $entry['Filter'] . '/', $entity['name'])) {
                $result[$entity['id']] = $this->to_utf8($entity['name']);
            }
        }

        $groups[$entry['Id']]['values'] = $result;
    }

    /*
    * checks if all mandatory fields have been filled
    *
    * @return {array} telling us if there is a missing parameter
    */
    public function validateFormatPopup() {
        $result = array('code' => 0, 'message' => 'ok');

        $this->validateFormatPopupLists($result);

        return $result;
    }

    /*
    * brings all parameters together in order to build the ticket arguments and save
    * ticket data in the database
    *
    * @param {object} $db_storage centreon storage database informations
    * @param {array} $contact centreon contact informations
    * @param {array} $host_problems centreon host information
    * @param {array} $service_problems centreon service information
    * @param {array} $extraTicketArguments
    *
    * @return {array} $result will tell us if the submit ticket action resulted in a ticket being opened
    */
    protected function doSubmit($db_storage, $contact, $host_problems, $service_problems, $extraTicketArguments=array()) {
        // initiate a result array
        $result = array(
            'ticket_id' => null,
            'ticket_error_message' => null,
            'ticket_is_ok' => 0,
            'ticket_time' => time()
        );

        // initiate smarty variables
        $tpl = new Smarty();
        $tpl = initSmartyTplForPopup($this->_centreon_open_tickets_path, $tpl, 'providers/Abstract/templates',
        $this->_centreon_path);

        $tpl->assign('centreon_open_tickets_path', $this->_centreon_open_tickets_path);
        $tpl->assign('user', $contact);
        $tpl->assign('host_selected', $host_problems);
        $tpl->assign('service_selected', $service_problems);
        // assign submitted values from the widget to the template
        $this->assignSubmittedValues($tpl);

        $ticketArguments = $extraTicketArguments;
        if (isset($this->rule_data['clones']['mappingTicket'])) {
            // for each ticket argument in the rule form, we retrieve its value
            foreach ($this->rule_data['clones']['mappingTicket'] as $value) {
                $tpl->assign('string', $value['Value']);
                $resultString = $tpl->fetch('eval.ihtml');
                if ($resultString == '') {
                    $resultstring = null;
                }
                $ticketArguments[$this->_internal_arg_name[$value['Arg']]] = $resultString;
            }
        }

        // we try to open the ticket
        try {
            $this->createTicket($ticketArguments);
        } catch (\Exception $e) {
            $result['ticket_error_message'] = $e->getMessage();
            return $result;
        }

        // we save ticket data in our database
        $this->saveHistory($db_storage, $result, array(
            'contact' => $contact,
            'host_problems' => $host_problems,
            'service_problems' => $service_problems,
            'ticket_value' => $this->glpiCallResult['response']['id'],
            'subject' => $ticketArguments[self::ARG_TITLE],
            'data_type' => self::DATA_TYPE_JSON,
            'data' => json_encode($ticketArguments)
        ));
        return $result;
    }


    /*
    * test if we can reach Glpi webservice with the given Configuration
    *
    * @param {array} $info required information to reach the glpi api
    *
    * @return {bool}
    *
    * throw \Exception if there are some missing parameters
    * throw \Exception if the connection failed
    */
    static public function test($info) {
        // this is called through our javascript code. Those parameters are already checked in JS code.
        // but since this function is public, we check again because anyone could use this function
        if (!isset($info['address']) || !isset($info['api_path']) || !isset($info['user_token'])
            || !isset($info['app_token']) || !isset($info['protocol'])) {
                throw new \Exception('missing arguments', 13);
        }

        // check if php curl is installed
        if (!extension_loaded("curl")) {
            throw new \Exception("couldn't find php curl", 10);
        }

        $curl = curl_init();

        $apiAddress = $info['protocol'] . '://' . $info['address'] . $info['api_path'] . '/initSession';
        $info['method'] = 0;
        // set headers
        $info['headers'] = array(
            'App-Token: ' . $info['app_token'],
            'Authorization: user_token ' . $info['user_token'],
            'Content-Type: application/json'
        );

        // initiate our curl options
        curl_setopt($curl, CURLOPT_URL, $apiAddress);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $info['headers']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_POST, $info['method']);
        curl_setopt($curl, CURLOPT_TIMEOUT, $info['timeout']);
        // execute curl and get status information
        $curlResult = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpCode > 301) {
            throw new Exception('curl result: ' . $curlResult . '|| HTTP return code: ' . $httpCode, 11);
        }

        return true;
    }

    /*
    * Get a session token from Glpi
    *
    * @return {string} the session token
    *
    * throw \Exception if no api information has been found
    * throw \Exception if the connection failed
    */
    protected function initSession() {
        // add the api endpoint and method to our info array
        $info['query_endpoint'] = '/initSession';
        $info['method'] = 0;
        // set headers
        $info['headers'] = array(
            'App-Token: ' . $this->_getFormValue('app_token'),
            'Authorization: user_token ' . $this->_getFormValue('user_token'),
            'Content-Type: application/json'
        );
        // try to call the rest api
        try {
            $curlResult = $this->curlQuery($info);
            $this->setCache('session_token', $curlResult['session_token'], 8 * 3600);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }

        return $curlResult['session_token'];
    }

    /*
    * handle every query that we need to do
    *
    * @param {array} $info required information to reach the glpi api
    *
    * @return {array} $curlResult the json decoded data gathered from glpi
    *
    * throw \Exception 10 if php-curl is not installed
    * throw \Exception if we can't get a session token
    * throw \Exception 11 if glpi api fails
    */
    protected function curlQuery($info) {
        // check if php curl is installed
        if (!extension_loaded("curl")) {
            throw new \Exception("couldn't find php curl", 10);
        }

        // if we aren't trying to initiate the session, we try to get the session token from the cache
        if ($info['query_endpoint'] != '/initSession') {
            $sessionToken = $this->getCache('session_token');
            // if the token wasn't found in cache we initiate the session to get one and put it in cache
            if (is_null($sessionToken)) {
                try {
                    $sessionToken = $this->initSession();
                    $this->setCache('session_token', $sessionToken, 8 * 3600);
                    array_push($info['headers'], 'Session-Token: ' . $sessionToken);
                } catch (\Exception $e) {
                    throw new \Exception($e->getMessage(), $e->getCode());
                }
            } else {
                array_push($info['headers'], 'Session-Token: ' . $sessionToken);
            }
        }

        $curl = curl_init();

        $apiAddress = $this->_getFormValue('protocol') . '://' . $this->_getFormValue('address') .
            $this->_getFormValue('api_path') . $info['query_endpoint'];

        // initiate our curl options
        curl_setopt($curl, CURLOPT_URL, $apiAddress);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $info['headers']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_POST, $info['method']);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->_getFormValue('timeout'));
        // add postData if needed
        if ($info['method']) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $info['postFields']);
        }
        // change curl method with a custom one (PUT, DELETE) if needed
        if (isset($info['custom_request'])) {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $info['custom_request']);
        }

        // if proxy is set, we add it to curl
        if ($this->_getFormValue('proxy_address') != '' && $this->_getFormValue('proxy_port') != '') {
                curl_setopt($curl, CURLOPT_PROXY, $this->_getFormValue('proxy_address') . ':' . $this->_getFormValue('proxy_port'));

            // if proxy authentication configuration is set, we add it to curl
            if ($this->_getFormValue('proxy_username') != '' && $this->_getFormValue('proxy_password') != '') {
                curl_setopt($curl, CURLOPT_PROXYUSERPWD, $this->_getFormValue('proxy_username') . ':' . $this->_getFormValue('proxy_password'));
            }
        }

        // execute curl and get status information
        $curlResult = json_decode(curl_exec($curl), true);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        // if http is 401 and message is about token, perhaps the token has expired, so we get a new one
        if ($httpCode == 401 && $curlResult[0] == 'ERROR_SESSION_TOKEN_INVALID') {
            try {
                $this->initSession();
                $this->curlQuery($info);
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage(), $e->getCode());
            }
        // for any other issue, we throw an exception
        } elseif ($httpCode >= 400) {
            throw new Exception('curl result: ' . $curlResult . '|| HTTP return code: ' . $httpCode, 11);
        }

        return $curlResult;
    }


    /*
    * get entities from glpi
    *
    * @return {array} $this->glpiCallResult['response'] list of entities
    *
    * throw \Exception if we can't get entities data
    */
    protected function getEntities() {
        // add the api endpoint and method to our info array
        $info['query_endpoint'] = '/getMyEntities/?is_recursive=1';
        $info['method'] = 0;
        // set headers
        $info['headers'] = array(
            'App-Token: ' . $this->_getFormValue('app_token'),
            'Content-Type: application/json'
        );
        // try to get entities from Glpi
        try {
            // the variable is going to be used outside of this method.
            $this->glpiCallResult['response'] = $this->curlQuery($info);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }

        return $this->glpiCallResult['response'];
    }

    /*
    * handle ticket creation in glpi
    *
    * @params {array} $ticketArguments contains all the ticket arguments
    *
    * @return {bool}
    *
    * throw \Exception if we can't open a ticket
    */
    protected function createTicket($ticketArguments) {
        // add the api endpoint and method to our info array
        $info['query_endpoint'] = '/Ticket';
        $info['method'] = 1;
        // set headers
        $info['headers'] = array(
            'App-Token: ' . $this->_getFormValue('app_token'),
            'Content-Type: application/json'
        );

        $fields['input'] = array(
            'name' => $ticketArguments['title'],
            'content' => $ticketArguments['content'],
            'entities_id' => $ticketArguments['entity'],
            'urgency' => $ticketArguments['urgency']
        );

        $info['postFields'] = json_encode($fields);

        try {
            $this->glpiCallResult['response'] = $this->curlQuery($info);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }

        return 0;
    }

    /*
    * close a ticket in Glpi
    *
    * @params {string} $ticketId the ticket id
    *
    * @return {bool}
    *
    * throw \Exception if it can't close the ticket
    */
    protected function closeTicketGlpi($ticketId) {
        // add the api endpoint and method to our info array
        $info['query_endpoint'] = '/Ticket/' . $ticketId;
        $info['method'] = 1;
        $info['custom_request'] = 'PUT';
        // set headers
        $info['headers'] = array(
            'App-Token: ' . $this->_getFormValue('app_token'),
            'Content-Type: application/json'
        );

        // status 6 = closed ticket
        $fields['input'] = array(
            'status' => 6
        );

        $info['postFields'] = json_encode($fields);

        try {
            $this->glpiCallResult['response'] = $this->curlQuery($info);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }

        return 0;
    }

    /*
    * check if the close option is enabled, if so, try to close every selected ticket
    *
    * @param {array} $tickets
    *
    * @return {void}
    */
    public function closeTicket(&$tickets) {
        if ($this->doCloseTicket()) {
            foreach ($tickets as $k => $v) {
                try {
                    $this->closeTicketGlpi($k);
                    $tickets[$k]['status'] = 2;
                } catch (\Exception $e) {
                    $tickets[$k]['status'] = -1;
                    $tickets[$k]['msg_error'] = $e->getMessage();
                }
            }
        } else {
            parent::closeTicket($tickets);
        }
    }


}
