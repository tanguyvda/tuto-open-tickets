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
    }

    protected function _setDefaultValueMain($body_html = 0) {

    }

    protected function _checkConfigForm() {

    }

    protected function _getConfigContainer1Extra() {

    }

    protected function _getConfigContainer2Extra() {

    }

    protected function saveConfigExtra() {

    }

    protected function getGroupListOptions() {

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
