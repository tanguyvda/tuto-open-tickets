## Table of contents
1. [INTRODUCTION](#introduction)
2. [STARTING OUR PROJECT](#start-our-project)
3. [CREATE YOUR CODE STRUCTURE](#create-your-code-structure)
4. [FORM BASICS](#form-basics)
    - [Default options](#default-options)
    - [Display our default options](#display-our-default-options)
    - [Save our first rule](#save-our-first-rule)
    - [Check the saved data](#check-the-saved-data)
5. [TICKET ARGUMENTS](#ticket-arguments)
    - [Arguments initialization](#arguments-initialization)
    - [Default ticket arguments options](#default-ticket-arguments-options)
    - [Display default ticket arguments](#display-default-ticket-arguments)
6. [PREPARING THE WIDGET](#preparing-the-widget)
    - [Initiate a custom argument listing in the widget](#initiate-a-custom-argument-listing-in-the-widget)
    - [Adding values to our custom listing](#adding-values-to-our-custom-listing)
    - [Initiate a ticket argument listing](#initiate-a-ticket-argument-listing)
7. [GATHER TICKET ARGUMENTS FROM THE TICKETING SOFTWARE](#gather-ticket-arguments-from-the-ticketing-software)
    - [Code structure](#code-structure)
    - [Test the API connection](#test-the-api-connection)
    - [Add a seperate api call system](#add-a-seperate-api-call-system)
    - [Get entities from Glpi](#get-entities-from-glpi)
8. [OPENING OUR FIRST TICKET](#opening-our-first-ticket)
    - [Create ticket function](#create-ticket-function)
    - [Open a ticket from the widget](#open-a-ticket-from-the-widget)
9. [CLOSING A TICKET](#closing-a-ticket)
    - [Enabling advanced close mode](#enabling-advanced-close-mode)
    - [Activating the close function on the PHP side](#activating-the-close-function-on-the-php-side)
10. [ADVANCED CONFIGURATION](#advanced-configuration)
    - [Using cache to our advantage](#advanced-configuration)
        - [Steps rundown when you open a ticket](#steps-rundown-when-you-open-a-ticket)
        - [Putting entities in cache](#putting-entities-in-cache)
        - [Put session token in cache](#put-session-token-in-cache)
    - [Add proxy configuration](#add-proxy-configuration)
    - [Preview of the API URL](#preview-of-the-api-url)
11. [CONCLUSION](#conclusion)

## INTRODUCTION <a name="introduction"></a>
This documentation is here to help you go through the development of a Centreon open tickets provider.
We will use GLPI as an ITSM software and Centreon 19.10

## STARTING OUR PROJECT <a name="start-our-project"></a>

- first of all, you need to register your provider.

`cat /usr/share/centreon/www/modules/centreon-open-tickets/providers/register.php`

```php
/*
 * Copyright 2015-2019 Centreon (http://www.centreon.com/)
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

$register_providers = array();

// provider name and the ID. For specific use id > 1000.
$register_providers['Mail'] = 1;
$register_providers['Glpi'] = 2;
$register_providers['Otrs'] = 3;
$register_providers['Simple'] = 4;
$register_providers['BmcItsm'] = 5;
$register_providers['Serena'] = 6;
$register_providers['BmcFootprints11'] = 7;
$register_providers['Easyvista'] = 8;
$register_providers['ServiceNow'] = 9;
$register_providers['Jira'] = 10;

// our custom provider
$register_providers['TutoGlpi'] = 27121991;
```

This step is easy, you register your provider, its name is going to be **TutoGlpi**. Its id is going to be **27121991**.
This ID is just used by centreon open ticket internally and won't be used in our provider itself.

- then you need to create the appropriate directory for your provider and your main provider code file.

`mkdir /usr/share/centreon/www/modules/centreon-open-tickets/providers/TutoGlpi`

`touch /usr/share/centreon/www/modules/centreon-open-tickets/providers/TutoGlpi/TutoGlpiProvider.class.php`

## CREATE YOUR CODE STRUCTURE <a name="create-your-code-structure"></a>

- open the TutoGlpiProvider.class.php file and start improvising

`vim /usr/share/centreon/www/modules/centreon-open-tickets/providers/TutoGlpi/TutoGlpiProvider.class.php`
```php
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

   protected function _setDefaultValueExtra() {

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
```

At this step, you should have the following result in the Configuration -> Notifications -> Rules menu
![rule](images/code_structure.png)

*note that at this point, you shouldn't have any PHP Notice nor any Error in your logfile
(/var/opt/rh/rh-php72/log/php-fpm/centreon-error.log)*

## FORM BASICS <a name="form-basics"></a>  
In this chapter, step by step, we're going to enhance the form that we've initiated.

### Default options <a name="default-options"></a>
First of all, we need to let people fill the default options to reach the Glpi REST API.
What we need are:
- an address for the Glpi server
- a path for its REST API
- a protocol to use
- a user token
- an app token
- a connection timeout

So let's get started and hope for the best.

```php
/*
* Set default values for our rule form options
*
* @return {void}
*/
protected function _setDefaultValueExtra() {
  $this->default_data['address'] = '10.30.2.46';
  $this->default_data['api_path'] = '/glpi/apirest.php';
  $this->default_data['protocol'] = 'https';
  $this->default_data['user_token'] = '';
  $this->default_data['app_token'] = '';
  $this->default_data['timeout'] = 60;
}
```

By now, this change has done nothing, because Centreon is using Smarty as a template engine. We need to configure
that in order to have our form displayed on the web interface.

### Display our default options <a name="display-our-default-options"></a>
In order to get a better understanding of what we're doing, we are going to quickly initiate our template engine Smarty

```php
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
  $tpl->assign('header', array('TutoGlpi' => _("Tuto Glpi Configuration Part")));

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
      'label' => _('Address'),
      'html' => $address_html
    ),
    'api_path' => array(
      'label' => _('API path'),
      'html' => $api_path_html
    ),
    'protocol' => array(
      'label' => _('Protocol'),
      'html' => $protocol_html
    ),
    'user_token' => array(
      'label' => _('User token'),
      'html' => $user_token_html
    ),
    'app_token' => array(
      'label' => _('APP token'),
      'html' => $app_token_html
    ),
    'timeout' => array(
      'label' => _('Timeout'),
      'html' => $timeout_html
    )
  );

  $tpl->assign('form', $array_form);
  $this->_config['container1_html'] .= $tpl->fetch('conf_container1extra.ihtml');
}
```

Now that everything seems to be set up, we need to create our template file, the so called conf_container1extra.ihtml
- Create a template directory

`mkdir /usr/share/centreon/www/modules/centreon-open-tickets/TutoGlpi/providers/templates`

- Create your template file

`touch /usr/share/centreon/www/modules/centreon-open-tickets/TutoGlpi/providers/templates/conf_container1extra.ihtml`

write the following html code in your template file
```html
<tr class="list_lvl_1">
  <td class="ListColLvl1_name" colspan="2">
    <h4>{$header.TutoGlpi}</h4>
  </td>
</tr>
<tr class="list_one">
  <td class="FormRowField">
    {$form.address.label}
  </td>
  <td class="FormRowValue">
    {$form.address.html}
  </td>
</tr>
<tr class="list_two">
  <td class="FormRowField">
    {$form.api_path.label}
  </td>
  <td class="FormRowValue">
    {$form.api_path.html}
  </td>
</tr>
<tr class="list_one">
  <td class="FormRowField">
    {$form.protocol.label}
  </td>
  <td class="FormRowValue">
    {$form.protocol.html}
  </td>
</tr>
<tr class="list_two">
  <td class="FormRowField">
    {$form.user_token.label}
  </td>
  <td class="FormRowValue">
    {$form.user_token.html}
  </td>
</tr>
<tr class="list_one">
  <td class="FormRowField">
    {$form.app_token.label}
  </td>
  <td class="FormRowValue">
    {$form.app_token.html}
  </td>
</tr>
<tr class="list_two">
  <td class="FormRowField">
    {$form.timeout.label}
  </td>
  <td class="FormRowValue">
    {$form.timeout.html}
  </td>
</tr>
```

When done, you should have the following result and still no errors (or notices) in your log file

![web ui with glpi server info](images/add_glpi_server_params.png)

### Save our first rule <a name="save-our-first-rule"></a>

Now that we have a somewhat cool looking form, fill it with some random information like on the screenshot below

![fake configuration](images/fake_configuration.png)

When done, just click the save button. You should be redirected to the rule menu and your rule should appear.
Click on it to edit its configuration.
If all went according to the plan, you've lost all your configuration (there's an easy broker configuration form joke to do there)
The reason is that, we've created the form, but never configured the save function. So here we go.

```php
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
}
```

You've made it, now, if you delete your old failed rule, create a new one, fill our fields with random data and save the form.
It should be saved.

### Check the saved data <a name="check-the-saved-data"></a>
What if I tell you that, you shouldn't let people save the form without having filled the mandatory fields that are:
- address
- API path
- Protocol
- User token
- App token

you should remember that we've made an array called `$array_form`. Now that we've listed the mandatory parameters, we're going
to edit their corresponding label by adding the `$this->_required_field` value. This just adds a small red star next to the label
so people know that it is mandatory.

```php
// this array is here to link a label with the html code that we've wrote above
$array_form = array(
  'address' => array(
    'label' => _('Addres') . $this->_required_field,
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
  )
);
```

But people may be blind, so we need to add an extra layer of security

```php
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

    $this->_checkFormValue('address', 'Please set "address" value');
    $this->_checkFormValue('api_path', 'Please set "API path" value');
    $this->_checkFormValue('protocol', 'Please set "Protocol" value');
    $this->_checkFormValue('user_token', 'Please set "User token" value');
    $this->_checkFormValue('app_token', 'Please set "APP token" value');
    // you know what? we're going to check if the timeout is an integer too
    $this->_checkFormInteger('timeout', '"Timeout" must be an integer');

    $this->_checkLists();

    if ($this->_check_error_message != '') {
      throw new Exception($this->_check_error_message);
    }
  }
```

We're done with this for the moment. Go try it out by removing the configuration of one of the mandatory fields and save the form
like on the screenshot below
![mandatory fields](images/mandatory_fields.png)

## TICKET ARGUMENTS <a name="ticket-arguments"></a>
Now is the time to start configuring our tickets arguments. We're going to start with a few parameters so everything
is understandable.
Here is the list of what we're going to configure:

- the body (content) of the ticket
- the subject of the ticket
- the entity linked to the ticket (this is a specific Glpi parameter)
- the urgency of the ticket (this is a specific Glpi parameter)

### Arguments initialization <a name="arguments-initialization"></a>
Let's jump back to the top of our class and initiate a few variables

```php
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

    // ... code ... //
}
```

This is where things can be confusing for beginners. So we're going to add a bit of debug there.
We're going to put the debug inside the `_getConfigContainer1Extra()` function. So your function should look like that:

```php
 protected function _getConfigContainer1Extra() {
     $file = fopen("/var/opt/rh/rh-php72/log/php-fpm/tuto.log", "a") or die ("Unable to open file!");
     fwrite($file, print_r($this->_internal_arg_name,true));
     fclose($file);

     // ... code .... //
}
```

If you go on the rule form page, nothing should have changed but a new logfile should have appeared in
`/var/opt/rh/rh-php72/log/php-fpm/tuto.log`

```php
Array
(
    [1] => content
    [2] => entity
    [3] => urgency
    [4] => title
)
```
Now that you've seen the debug, we can remove the fopen, fwrite and fclose statement that we've added.

### Default ticket arguments options <a name="default-ticket-arguments-options"></a>
Like we did at the beginning, we need to let people configure those parameters

```php
/*
* Set default values for our rule form options
*
* @return {void}
*/
protected function _setDefaultValueExtra() {
  $this->default_data['address'] = '10.30.2.46';
  $this->default_data['api_path'] = '/glpi/apirest.php';
  $this->default_data['protocol'] = 'https';
  $this->default_data['user_token'] = '';
  $this->default_data['app_token'] = '';
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
```

### Display default ticket arguments <a name="display-default-ticket-arguments"></a>
We are going to face the same issue than before, nothing is going to be displayed on our webinterface if
we don't link it to our template one way or another.

```php
/*
* Initiate your html configuration and let Smarty display it in the rule form
*
* @return {void}
*/
protected function _getConfigContainer1Extra() {

  // ... code ... //

  $array_form = array(
    'address' => array(
      'label' => _('Addres'),
      'html' => $address_html
    ),
    'api_path' => array(
      'label' => _('API path'),
      'html' => $api_path_html
    ),
    'protocol' => array(
      'label' => _('Protocol'),
      'html' => $protocol_html
    ),
    'user_token' => array(
      'label' => _('User token'),
      'html' => $user_token_html
    ),
    'app_token' => array(
      'label' => _('APP token'),
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
```

Now that our html is ready, we need to adapt our template file `conf_container1extra.ihtml`
add the end of this file add:

```html

<!-- ... code ... -->

<tr class="list_two">
    <td class="FormRowField">
        {$form.mappingTicketLabel.label}
    </td>
    <td class="FormRowValue">
        {include file="file:$centreon_open_tickets_path/providers/Abstract/templates/clone.ihtml" cloneId="mappingTicket" cloneSet=$form.mappingTicket}
    </td>
</tr>
```

We're good now, let's take a look at our work. Go back to your rule form and check what happened.

![ticket args](images/ticket_args1.png)

That's a bit disapointing, all that code, just to get a text, a small button and a grammar mistake (if you look closely, you'll be able to *find* it)

Well in fact, if we click on **Add a new entry** we can see a part of our work.
By clicking on it, you should be able to understand the naming of what we worked on:

`$this->default_data['clones']['mappingTicket']`

we are cloning elements and by doing it, we want to map a ticket argument (content, title, entity or urgency) with a value.

To have a better overview of that, just go back to the rule menu, create a new rule, select TutoGlpi as a rule, and you'll each ticket arguments linked (mapped) with a value.

![ticket args](images/ticket_args2.png)

### Save ticket arguments  <a name="save-ticket-arguments"></a>
History repeats itself, and here we are gain, with a form that won't save itself. If you've been attentive you should know
where we're heading.

If you're not quite confident with what is happening, just try to create a new rule and add some random data like below

![ticket save args](images/ticket_args_save1.png)

Then, if you save the form and go back on it every ticket args should have disappeared.

![ticket save args](images/ticket_args_save2.png)

This is easy to fix, we just need to add our ticket arguments to the save function that we've created earlier.

```php
/*
* Saves the rule form in the database
*
* @return {void}
*/
protected function saveConfigExtra() {
  $this->_save_config['simple']['address'] = $this->_submitted_config['address'];
  $this->_save_config['simple']['user_token'] = $this->_submitted_config['user_token'];
  $this->_save_config['simple']['protocol'] = $this->_submitted_config['protocol'];
  $this->_save_config['simple']['app_token'] = $this->_submitted_config['app_token'];
  $this->_save_config['simple']['api_path'] = $this->_submitted_config['api_path'];
  $this->_save_config['simple']['timeout'] = $this->_submitted_config['timeout'];

  // saves the ticket arguments
  $this->_save_config['clones']['mappingTicket'] = $this->_getCloneSubmitted('mappingTicket', array('Arg', 'Value'));
}
```

Now that it is done, you should be able to save your form properly.

## PREPARING THE WIDGET <a name="preparing-the-widget"></a>
Things are getting serious and to have a better understanding of the code that we're going to write, we need to
save a real configuration.

- address: 10.30.2.46
- api_path: /glpi/apirest.php
- protocol: http
- user_token: cYpJTf0SAPHHGP561chJJxoGV2kivhDv3nFYxQbl
- app_token: f5Rm9t5ozAyhcHDpHoMhFoPapi49TAVsXBZwulMR

![real config](images/real_config.png)

Now is the time to get a glimpse on what is happening on our widget. Add an open tickets widget in a custom view.
Configure it so it uses your glpi rule and try to open a ticket. You should have something that looks like that:

![fail widget](images/widget-fail.gif)

### Initiate a custom argument listing in the widget <a name="initiate-a-custom-argument-listing-in-the-widget"></a>
We are going to start with the Urgency. Because it is going to be what we call a custom list. Meaning that
the value of the Urgency is not going to be gathered from Glpi but from a list created by the user.

Here we add the Urgency in the rule configuration form in order to have it displayed when we open a ticket.

```php
/*
* Set default values for our rule form options
*
* @return {void}
*/
protected function _setDefaultValueMain($body_html = 0) {
  parent::_setDefaultValueMain($body_html);

  $this->default_data['clones']['groupList'] = array(
    array(
      'Id' => 'urgency',
      'Label' => _('Urgency'),
      'Type' => self::CUSTOM_TYPE,
      'Filter' => '',
      'Mandatory' => ''
    )
  );
}
```

to test this code, create a new rule using our provider. You should have the following option in your form.
![custom list](images/custom_list1.png)

save your configuration and try to open a ticket with your widget. You should have something like this:
![widget options](images/widget_options1.png)

### Adding values to our custom listing <a name="adding-values-to-our-custom-listing"></a>
What can be cool is to have an already configured listing in our rule form.

To display our listing in the widget, we need to initiate them in the rule form
```php
protected function _setDefaultValueMain(){
  // ... code ... //

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
```

Now that you're done configuring the custom list, if you try to create a new rule, you should have
the following list:
![custom list](images/custom_list2.png)

you can finish configure this new rule or just take the old one and manually configure the custom list.
When done, open a ticket through the widget and you should have the following list that appears:
![widget options](images/widget_options2.png)

### Initiate a ticket argument listing <a name="initiate-a-ticket-argument-listing"></a>
Just before, we used a custom list. The type of the list was `self::CUSTOM_TYPE`. And if we jump back to
[Arguments initialization](#arguments-initialization), we see that we've created a `GLPI_ENTITIES_TYPE`. Let's
make the best out of it

```php
protected function _setDefaultValueMain($body_html = 0) {
  parent::_setDefaultValueMain($body_html);

  $this->default_data['clones']['groupList'] = array(
    array(
      'Id' => 'urgency',
      'Label' => _('Urgency'),
      'Type' => self::CUSTOM_TYPE,
      'Filter' => '',
      'Mandatory' => ''
    ),
    array(
      'Id' => 'glpi_entity',
      'Label' => _('Entity'),
      'Type' => self::GLPI_ENTITIES_TYPE,
      'Filter' => '',
      'Mandatory' => ''
    )
  );

  // ... code ... //
}
```
In order to have the GLPI_ENTITIES_TYPE correctly defined, we need to add this option to listing that is already composed of

- Host group
- Host category
- Host severity
- Service group
- Service severity
- Contact group
- Body
- Custom
- Glpi entities (coming soon)

To improve this listing we need to add the following code:

```php
/*
* Adds new types to the list of types
*
* @return {string} $str html code that add an option to a select
*/
protected function getGroupListOptions() {
  $str = '<option value="' . self::GLPI_ENTITIES_TYPE . '">Glpi entities</option>';

  return $str
}
```
Now, if you try to create a new rule form, you'll have the following option:
![custom list](images/custom_list3.png)

Needless to go in your widget to test this out. You won't see any new list there. That's because the data will
come from the ticketing software. And we yet have to gather this data. We will see that in the next chapter. Now that we are done configuring the form (well, done for the basics), we are going to create our own code.

## GATHER TICKET ARGUMENTS FROM THE TICKETING SOFTWARE <a name="gather-ticket-arguments-from-the-ticketing-software"></a>
**DISCLAIMER: this tutorial is not here to teach people how to code. I don't consider myself as the best PHP developper ever.
There may be ways to improve what we are going to do**

In this chapter we're going to get informations from Glpi and find a way to link them to our previously made listing. To do so, this is the first time we're going to write our own code. Obviously, we can use other providers to help us.

### Code structure <a name="code-structure"></a>
Let's have a look at what we're expecting from the code now:

```php
// link the list type with computed data in order to display the listing in the widget
protected function assignOthers($entry, &$groups_order, &$groups) {

}

// compute the retrieved data from the ITSM software
protected function assignGlpiEntities($entry, &$groups_order, &$groups) {

}

// test restAPI connection
public function test($info) {

}

// handle curl queries
protected function curlQuery() {

}

// get entities from Glpi
protected function getEntities() {

}

// initiate the API session
protected function initSession($info) {

}
```

### Test the API connection <a name="test-the-api-connection"></a>
In this part, we're going to add a test button in the rule form. Test button that is going to
help us build a well rounded REST connection to Glpi. This is a rather important chapter since we are going to
work with smarty, PHP and ajax.
The function is public because it is going to be called from outside of the class

```php
/*
* test if we can reach Glpi webservice by getting a session token with the given Configuration
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
```
As you can see, every API related information is stored in a `$info` array.

### Add a seperate api call system <a name="add-a-seperate-api-call-system"></a>

```php
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
* @return {object|json} $curlResult the json data gathered from glpi
*
* throw \Exception 10 if php-curl is not installed
* throw \Exception 11 if glpi api fails
*/
protected function curlQuery($info) {
  // check if php curl is installed
  if (!extension_loaded("curl")) {
    throw new \Exception("couldn't find php curl", 10);
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

  // execute curl and get status information
  $curlResult = json_decode(curl_exec($curl), true);
  $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
  curl_close($curl);

  if ($httpCode > 301) {
    throw new Exception('curl result: ' . $curlResult . '|| HTTP return code: ' . $httpCode, 11);
  }

  return $curlResult;
}
```

PHP wise, we should be good. We have a test function that is going to return the state of our connection.
We have a function to initiate the session with Glpi. And finally, we have a function that is going to handle every curl query.

Now we need to create our ajax call. To do so, open your template file. `vi conf_container1extra.ihtml`
```html

<!-- ... code ... -->

<tr class="list_two">
  <td class="FormRowField">
    {t}Test authentication{/t}
  </td>
  <td class="FormRowValue">
    <button class="btc bt_action" id="test-glpi">{t}Test{/t}</button>
    <span id="test-error" class="error_message" style="display: none; color: red;"></span>
    <span id="test-ok" class="okay_message" style="display: none; color:green;"></span>
  </td>
</tr>

<!-- the code below was already there, i've changed the tr class right under this comment line -->
<tr class="list_one">
  <td class="FormRowField">
    {$form.mappingTicketLabel.label}
  </td>
  <td class="FormRowValue">
    {include file="file:$centreon_open_tickets_path/providers/Abstract/templates/clone.ihtml" cloneId="mappingTicket" cloneSet=$form.mappingTicket}
  </td>
</tr>
<!-- now we are going to write new code again -->

<script>
  var webServiceUrl = '{$webServiceUrl}'
</script>
{literal}
<script>
  // start the button on click event
  jQuery('#test-glpi').on('click', function (e) {
    e.preventDefault();
    jQuery('.error_message').hide();

    let fields = [
      'address',
      'api_path',
      'protocol',
      'app_token',
      'user_token'
    ];

    let i;
    let inError = false;
    let field;
    // check if each field is filled ...
    for (i = 0; i < fields.length; i++) {
      field = 'input[name="' + fields[i] + '"]';
      if (jQuery(field).val().trim() === '') {
        jQuery('#test-error').text('A required field is empty.');
        jQuery('#test-error').show();
        jQuery('#err-' + fields[i]).text('This field is required.').show();
        inError = true;
      }
    }

    // ... if not, end script execution
    if (inError) {
      return;
    }

    // start ajax callback
    jQuery.ajax({
      // call open ticket api with every needed parameter
      url: webServiceUrl + '?object=centreon_openticket&action=testProvider',
      type: 'POST',
      contentType: 'application/json',
      dataType: 'json',
      data: JSON.stringify({
        service: 'TutoGlpi', // this is the name of our provider
        address: jQuery('input[name="address"]').val(),
        api_path: jQuery('input[name="api_path"]').val(),
        protocol: jQuery('input[name="protocol"]').val(),
        app_token: jQuery('input[name="app_token"]').val(),
        user_token: jQuery('input[name="user_token"]').val(),
        timeout: jQuery('input[name="timeout"]').val()
      }),
      success: function (data) {
        if (data) {
          jQuery('#test-ok').text('Connection is ok');
          jQuery('#test-ok').show();
        } else {
          jQuery('#test-error').text('unknown issue');
          jQuery('#test-error').show();
        }
      },
      error: function (error) {
        jQuery('#test-error').text(error.responseText);
        jQuery('#test-error').show();
      }
    });
  })
</script>
{/literal}
```
If you are careful, you'll see that in the above code, we have a variable called `$webServiceUrl` that is coming from nowhere.
And if you start to understand how our template engine works, we need to assign a value to this variable. To do so, jump back to the **_getConfigContainer1Extra** function and make sure you assign a value to **webServieUrl** like below

```php
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
  $tpl->assign('header', array('TutoGlpi' => _("Tuto Glpi Configuration Part")));
  $tpl->assign('webServieUrl', './api/internal.php');

  // ... code ... //
}
```

If everything is done correctly, we should have the following result in our rule form:
![test button](images/test_connection.png)

### Get entities from Glpi <a name="get-entities-from-glpi"></a>
Since we've created a way to get a session token, we can use it at our advantage to get entities from Glpi.

```php
/*
* get entities from glpi
*
* @return {bool}
*
* throw \Exception if we can't get a session token
* throw \Exception if we can't get entities data
*/
protected function getEntities() {
  // get a session token
  try {
    $sessionToken = $this->initSession();
  } catch (\Exception $e) {
    throw new \Exception($e->getMessage(), $e->getCode());
  }

  // add the api endpoint and method to our info array
  $info['query_endpoint'] = '/getMyEntities/?is_recursive=1';
  $info['method'] = 0;
  // set headers
  $info['headers'] = array(
    'App-Token: ' . $this->_getFormValue('app_token'),
    'Session-Token: ' . $sessionToken,
    'Content-Type: application/json'
  );

  // try to get entities from Glpi
  try {
    // the variable is going to be used outside of this method.
    $this->glpiCallResult['response'] = $this->curlQuery($info);
  } catch (\Exception $e) {
    throw new \Exception($e->getMessage(), $e->getCode());
  }

  return true;
}
```

Okay, now we have something that is able to retrieve entities from Glpi. We need to call it now.

In the following method, we are going to link data from our configuration form and from what Glpi is going to give us.
This time, we're going to use three variables that are not very clear when you read them so here is quick explanation
and how they look like.

- $entry, you should see that it matches your configuration form with the mandatory, filter, sort options and such
```php
Array
(
  [Id] => glpi_entity
  [Label] => Entity
  [Type] => 10
  [Filter] =>
  [Mandatory] =>
  [Sort] =>
)
```

- $groups_order, just to order your arguments (not very clear since we only have the entity)

```php
  Array
  (
    [0] => glpi_entity
  )
```

- $groups, you should notice that it contains data retrieved from Glpi (we are going to get them now)

```php
Array
(
  [glpi_entity] => Array
  (
    [label] => Entity
    [values] => Array
    (
      [1] => Root entity > Amazing Platform
      [2] => Root entity > Centreon
      [3] => Root entity > Cluster
      [4] => Root entity > Databases
      [0] => Root entity
    )

  )

)
```

```php
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
      (isset($entry['Mandatory']) && $entry['Mandatory'] == 1 ? $this->_required_field : ''),
    'sort' => (isset($entry['Sort']) && $entry['Sort'] == 1 ? 1 : 0)
  );

  // adds our entry in the group order array
  $groups_order[] = $entry['Id'];

  // try to get entities
  try {
    $this->getEntities();
  } catch (\Exception $e) {
    $groups[$entry['Id']]['code'] = -1;
    $groups[$entry['Id']]['msg_error'] = $e->getMessage();
  }

  $result = array();
  /* this is what is inside $this->glpiCallResult['response'] at this point
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
  foreach ($this->glpiCallResult['response']['myentities'] as $entity) {
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

  $this->saveSession('glpi_entities', $this->glpiCallResult['response']);
  $groups[$entry['Id']]['values'] = $result;
}
```



```php
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
  // if the entry type is GLPI_ENTITIES_TYPE then we are going to configure it
  if ($entry['Type'] == self::GLPI_ENTITIES_TYPE) {
    $this->assignGlpiEntities($entry, $groups_order, $groups);
  }
}
```

At this point, we should have a nice widget that looks like this:
![widget with entities](images/widget-with-entities.gif)

## OPENING OUR FIRST TICKET <a name="opening-our-first-ticket"></a>
This is it, we've made it, we are now ready (this is a lie) to open our first ticket.
Well, in fact, we forgot one small step, we didn't create the code that will create the ticket.

### Create ticket function <a name="create-ticket-function"></a>
as we did for the **initSession** and **getEntities** we are going to write a **createTicket** method

```php
/*
* handle ticket creation in glpi
*
* @params {array} $ticketArguments contains all the ticket arguments
*
* @return {bool}
*
* throw \Exception if we can't get a session token
* throw \Exception if we can't open a ticket
*/
protected function createTicket($ticketArguments) {
  // get a session token
  try {
    $sessionToken = $this->initSession();
  } catch (\Exception $e) {
    throw new \Exception($e->getMessage(), $e->getCode());
  }

  // add the api endpoint and method to our info array
  $info['query_endpoint'] = '/Ticket';
  $info['method'] = 1;
  // set headers
  $info['headers'] = array(
    'App-Token: ' . $this->_getFormValue('app_token'),
    'Session-Token: ' . $sessionToken,
    'Content-Type: application/json'
  );

  // prepare the required post fields to create a ticket
  $fields['input'] = array(
    'name' => $ticketArguments['title'],
    'content' => $ticketArguments['content'],
    'entities_id' => $ticketArguments['entity'],
    'urgency' => $ticketArguments['urgency']
  );
  $info['postFields'] = json_encode($fields);

  // try to open a new ticket through the glpi api
  try {
    $this->glpiCallResult['response'] = $this->curlQuery($info);
  } catch (\Exception $e) {
    throw new \Exception($e->getMessage(), $e->getCode());
  }

  return true;
}
```
### Open a ticket from the widget <a name="open-a-ticket-from-the-widget"></a>
Now that we have a function that open a ticktet, it is required that we have a function that is going
to be called when we submit our ticket arguments from the widget. This function is called **doSubmit**

```php
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
    'ticket_value' => $this->glpiCallResult['response']['id'],
    'subject' => $ticketArguments[self::ARG_TITLE],
    'host_problems' => $host_problems,
    'service_problems' => $service_problems,
    'data_type' => self::DATA_TYPE_JSON,
    'data' => json_encode($ticketArguments)
  ));
  return $result;
}
```

and here we check if every field is filled as expected.
```php
/*
* checks if all mandatory fields have been filled
*
* @return {array} telling us if there is a missing parameter
*/
public function validateFormatPopup() {
      $result = array('code' => 0, 'message' => 'ok');

      // checks if every mandatory field is filled
      $this->validateFormatPopupLists($result);

      return $result;
}
```

at this point, you should be able to open ticket
![first ticket](images/first-ticket.gif)

## CLOSING A TICKET <a name="closing-a-ticket"></a>
One step at a time, we finally opened our ticket. Let's try to close it now.

### Enabling advanced close mode <a name="enabling-advanced-close-mode"></a>
There is an advanced close mode for our tickets, and we're going to enable it. To do so, at the beginning of our class,
add the following statement

```php
class TutoGlpiProvider extends AbstractProvider {
  protected $_close_advanced = 1;

  // ... code ... //
}
```

in your rule form, at the bottom, you should now have the following options:
![enable close ticket](images/enable_close_ticket.png)

**Don't forget to tick the enable option**

### Activating the close function on the PHP side <a name="activating-the-close-function-on-the-php-side"></a>
At the moment, if we try to close at ticket with a widget that is configured to handle this, we should have something like that

![fail close](images/fail_close.png)

The popup that doesn't show up the ticket id that i've closed is a sign of either a bug in the code, or in our case, no code
at all.

First of all, let's create a closeTicketGlpi function

```php
/*
* close a ticket in Glpi
*
* @params {string} $ticketId the ticket id
*
* @return {bool}
*
* throw \Exception if it can't get a session token
* throw \Exception if it can't close the ticket
*/
protected function closeTicketGlpi($ticketId) {

  try {
    $sessionToken = $this->initSession();
  } catch (\Exception $e) {
    throw new \Exception($e->getMessage(), $e-getCode());
  }

  // add the api endpoint and method to our info array
  $info['query_endpoint'] = '/Ticket/' . $ticketId;
  $info['method'] = 1;
  $info['custom_request'] = 'PUT';
  // set headers
  $info['headers'] = array(
    'App-Token: ' . $this->_getFormValue('app_token'),
    'Session-Token: ' . $sessionToken,
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
```

Now we need to call the function we created, and to do so, we are going to use the closeTicket function that is required

```php
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
```

We have now finished with the whole ticket process and should be able to close it.
![closing ticket](images/closing-ticket.gif)

## ADVANCED CONFIGURATION <a name="advanced-configuration"></a>
You thought we were done with this tutorial, you couldn't be more wrong. There are a lot of things
that we can improve. In this chapter, we're going to edit some of our previously done work to add some features or
improve performances. Keep in mind that you have ab already working Glpi provider and that what you're going to read
can be confusing since we are going to handle things differently.

### Using cache to our advantage <a name="advanced-configuration"></a>
At the moment, we're only getting one information from Glpi which is the entity. On a finished connector, we gather much more
information like categories for Glpi. Let's see what we're doing if we add categories

#### Steps rundown when you open a ticket <a name="steps-rundown-when-you-open-a-ticket"></a>

- ticket 1
    - get session token
    - get entities list
    - get session token
    - get categories
    - select an entity
    - select a category
    - get session token
    - open the ticket
- ticket 2
    - get session token
    - get entities list
    - get session token
    - get categories
    - select an entity
    - select a category
    - get session token
    - open the ticket
- ticket 3
    - get session token
    - get entities list
    - get session token
    - get categories
    - select an entity
    - select a category
    - get session token
    - open the ticket

That is not optimal. The obvious issue is coming from the session token.

What we aim for should be the following:

- ticket 1
    - get session token
    - set token in cache
    - get entities list
    - set list in cache
    - get categories
    - set list in cache
    - select an entity
    - select a category
    - open the ticket
- ticket 2 (now everything is in cache)
    - select an entity
    - select a category
    - open the ticket
- ticket 3 (everything is still in cache)
    - select an entity
    - select a category
    - open the ticket

#### Putting entities in cache <a name="putting-entities-in-cache"></a>
We're not going to use the session token as an example because it is a bit tricky. So here we go
with the **assignGlpiEntities** function. And we are going to add some debug to check if we are
getting our entities from the cache or not. In the following example, we  will print the cache or print
**no cache this time** if we didn't found data in cache. If you made a mistake like you forgot to return the
list of entities in the getEntities function, you can just log out of your centreon and log back in.

```php
protected function assignGlpiEntities($entry, &$groups_order, &$groups) {
  // ... code ... //
  try {
    //get entities from cache
    $listEntities = $this->getCache($entry['Id']);
    $file = fopen("/var/opt/rh/rh-php72/log/php-fpm/cache", "w") or die ("Unable to open file!");
    fwrite($file, print_r($listEntities,true));
    if (is_null($listEntities)) {
      fwrite($file, print_r('no cache this time', true));
      // if no entity found in cache, get them from glpi and put them in cache for 8 hours
      $listEntities = $this->getEntities();
      $this->setCache($entry['Id'], $listEntities, 8 * 3600);
    }
  } catch (\Exception $e) {
    $groups[$entry['Id']]['code'] = -1;
    $groups[$entry['Id']]['msg_error'] = $e->getMessage();
  }

  fclose($file);
  $result = array();
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
```

we also need to do a small modification on the **getEntities** function. if we want `$listEntities` to be
filled with value, we need **getEntities** to return those values and not just return true.

```php
/*
* ... comments ...
*
* @return {array} $this->glpiCallResult['response'] list of entities
*
* throw \Exception if we can't get a session token
* throw \Exception if we can't get entities data
*/
protected function getEntities() {

  // ... code ... //

  try {
    // the variable is going to be used outside of this method.
    $this->glpiCallResult['response'] = $this->curlQuery($info);
  } catch (\Exception $e) {
    throw new \Exception($e->getMessage(), $e->getCode());
  }

  return $this->glpiCallResult['response'];
}
```
#### Put session token in cache <a name="put-session-token-in-cache"></a>
Now that we have a better overview at putting information in cache. Let's try to do that with the
session token.
Here is the fully reworked **curlQuery** function (we will enhance it again in the next chapter about proxy):
```php
/*
* handle every query that we need to do
*
* @param {array} $info required information to reach the glpi api
*
* @return {array} $curlResult the json decoded data gathered from glpi
*
* throw \Exception 10 if php-curl is not installed
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
```
Now, the session initialization is handled by this function. There's no need to have it in the
**getEntities, createTicket and closeTicketGlpi** functions. So you just need to remove the
**try/catch** block at the beginning of each said functions and remove the `Session-Token:` entry from the
`$info['headers']` array. Here is the example coming from **getEntities**
```php
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
```
*I've also removed the throw \Exception about the session token since we don't handle it anymore*

### Add proxy configuration <a name="add-proxy-configuration"></a>
For whatever reason, we could need to use a proxy in order to reach the Glpi server.

```php

class TutoGlpiProvider extends AbstractProvider {
  protected $_close_advanced = 1;
  protected $_proxy_enabled = 1;

  const GLPI_ENTITIES_TYPE = 10;

  // ... code ... //
}
```
at this point, you should have the following result in your rule form:
![proxy settings](images/proxy_settings.png)

we just need to use them if they are configured

```php
static protected function curlQuery($info) {

  // ... code ... //

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
  if (isset($this->_getFormValue('proxy_address')) && $this->_getFormValue('proxy_address') != ''
    && isset($this->_getFormValue('proxy_port')) && $this->_getFormValue('proxy_port') != '') {
      curl_setopt($curl, CURLOPT_PROXY, $this->_getFormValue('proxy_address') . ':' . $this->_getFormValue('proxy_port'));

    // if proxy authentication configuration is set, we add it to curl
    if (isset($this->_getFormValue('proxy_username')) && $this->_getFormValue('proxy_username') != ''
      && isset($this->_getFormValue('proxy_password')) && $this->_getFormValue('proxy_password') != '') {
        curl_setopt($curl, CURLOPT_PROXYUSERPWD, $this->_getFormValue('proxy_username') . ':' . $this->_getFormValue('proxy_password'));
    }
  }
  // ... code ... //
}
```
If you still have memories of what we've done so far, you should remember that we made a test function. In case
of proxy, we need our test to use it. Open your **conf_container1extra.ihtml** file and edit the script part to add the proxy parameters.

```javascript

// ... code ... //

if (inError) {
  return;
}

jQuery.ajax({
  // call open ticket api with every needed parameter
  url: webServiceUrl + '?object=centreon_openticket&action=testProvider',
  type: 'POST',
  contentType: 'application/json',
  dataType: 'json',
  data: JSON.stringify({
    service: 'TutoGlpi', // this is the name of our provider
    address: jQuery('input[name="address"]').val(),
    api_path: jQuery('input[name="api_path"]').val(),
    protocol: jQuery('input[name="protocol"]').val(),
    app_token: jQuery('input[name="app_token"]').val(),
    user_token: jQuery('input[name="user_token"]').val(),
    timeout: jQuery('input[name="timeout"]').val(),
    proxy_address: jQuery('input[name="proxy_address"]').val(),
    proxy_port: jQuery('input[name="proxy_port"]').val(),
    proxy_username: jQuery('input[name="proxy_username"]').val(),
    proxy_password: jQuery('input[name="proxy_password"]').val()
  }),

// ... code ... //
})
```
at this point, proxy settings are going to work, but not because we

### Preview of the API URL <a name="preview-of-the-api-url"></a>
if you've watched closely what we were doing, you should have noticed that there is an Url field in our configuration form
We are going to fill it so it helps people understand how we use the address, api_path and protocol options.

```php
protected function _setDefaultValueMain($body_html = 0) {
  parent::_setDefaultValueMain($body_html);

  $this->default_data['url'] = '{$protocol}://{$address}{$api_path}';

  // ... code ... //
}
```

you should now have the url field filled as follow if you try to create a new rule:
![url configuration](images/url_configuration.png)

## CONCLUSION <a name="conclusion"></a>
You've made it, this tutorial is now finished.
