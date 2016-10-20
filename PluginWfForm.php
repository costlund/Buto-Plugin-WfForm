<?php

class PluginWfForm{
  /**
   * <p>Render a form.</p> 
   * <p>Consider to add data in separate xml file because you need to pic it up again when handle posting values. Use widget to handle post request if necessary.</p> 
   * <p>'yml:/theme/[theme]/form/my_form.yml'</p>
   */
  public static function widget_render($data){
    if(wfArray::isKey($data, 'data')){
      if(!is_array(wfArray::get($data, 'data'))){
        $filename = wfArray::get($GLOBALS, 'sys/app_dir').wfArray::get($data, 'data');
        if(file_exists($filename)){
          $data['data'] = sfYaml::load($filename);
        }else{
          throw new Exception("Could not find file $filename.");
        }
      }
    }
    
    
    $default = array(
        'submit_value' => 'Send',
        'submit_class' => 'btn btn-primary',
        'id' => str_replace('.', '', uniqid(mt_rand(), true)),
        'script' => null,
        'post_to_divzzz' => '',
        'ajax' => false,
        'url' => '/doc/form_url_is_not_set',
        'items' => array()
        );
    $default = array_merge($default, $data['data']);
    $default['url'] = wfSettings::replaceClass($default['url']);
    
    $buttons = array();
    if($default['post_to_divzzz']){
      $buttons[] = wfDocument::createHtmlElement('a', $default['submit_value'], array('class' => 'a_button', 'onclick' => "wfPostForm(wfElement('".$default['id']."'), '".$default['url']."', '".$default['post_to_div']."');return false;"));
    }  elseif($default['ajax']) {
      
      $onclick = "$.post('".$default['url']."', $('#".$default['id']."').serialize()).done(function(data) { PluginWfCallbackjson.call( data ); });return false;";
      
      $buttons[] = wfDocument::createHtmlElement('input', null, array('type' => 'submit', 'value' => $default['submit_value'], 'class' => $default['submit_class'], 'onclick' => $onclick, 'id' => $default['id'].'_save'));
    }  else {
      $buttons[] = wfDocument::createHtmlElement('input', null, array('type' => 'submit', 'value' => $default['submit_value'], 'class' => $default['submit_class']));
    }
    if(isset($data['data']['buttons'])) { 
      foreach ($data['data']['buttons'] as $key => $value) {
        $buttons[] = wfDocument::createHtmlElement($value['type'], $value['innerHTML'], $value['attribute']);
      }
    }
    
    $form_element = array();
    foreach ($default['items'] as $key => $value) {
      $default_value = array(
          'lable' => $key,
          'default' => '',
          'element_id' => $default['id'].'_'.$key,
          'name' => $key,
          'readonly' => null,
          'type' => null,
          'checked' => null,
          'mandatory' => null,
          'option' => null,
          'wrap' => null,
          'class' => 'form-control',
          'style' => null
              );
      $default_value = array_merge($default_value, $value);
      if($default_value['mandatory']){$default_value['lable'] .= '*';}
      $type = null;
      $innerHTML = null;
      $attribute = array('name' => $default_value['name'], 'id' => $default_value['element_id'], 'class' => $default_value['class'], 'style' => $default_value['style']);
      switch ($default_value['type']) {
        case 'checkbox':
          $type = 'input';
          $attribute['type'] = 'checkbox';
          if($default_value['checked'] || $default_value['default']=='1'){
            $attribute['checked'] = 'checked';
          }
          break;
        case 'text':
          $type = 'textarea';
          $attribute['wrap'] = $default_value['wrap'];
          $innerHTML = $default_value['default'];
          break;
        case 'password':
          $type = 'input';
          $attribute['type'] = 'password';
          $attribute['value'] = $default_value['default'];
          break;
        case 'varchar':
          if(!$default_value['option']){
            $type = 'input';
            $attribute['type'] = 'text';
            $attribute['value'] = $default_value['default'];
          }else{

            $type = 'select';
            $option = array();
            foreach ($default_value['option'] as $key2 => $value2) {
              $temp = array();
              $temp['value'] = $key2;
              //if($default_value['default']==$key2){
              if((string)$default_value['default']===(string)$key2){
                $temp['selected'] = 'true';
              }
              $option[] = wfDocument::createHtmlElement('option', $value2, $temp);
            }
            $innerHTML = $option;
          }
          break;
        case 'hidden':
          $type = 'input';
          $attribute['type'] = 'hidden';
          $attribute['value'] = $default_value['default'];
          break;
        case 'div':
          $type = 'div';
          break;
        default:
          break;
      }
      if($type){
        if($type=='div'){
          $form_element[] = $value;
        }else{
          $temp = array();
          if(wfArray::get($attribute, 'type') != 'hidden'){
            //$temp['div'] = wfDocument::createHtmlElement('div', array(wfDocument::createHtmlElement('label', $default_value['lable'], array('for' => $default_value['element_id']))), array('class' => ''));
            $temp['lable'] = wfDocument::createHtmlElement('label', $default_value['lable'], array('for' => $default_value['element_id']));
          }
          
          /**
           * Add Bootstrap glyphicon.
           */
          if(wfArray::get($value, 'info/text')){
            $temp['glyphicon_info'] = wfDocument::createHtmlElement('span', null, array(
                'title' => $default_value['lable'], 
                'class' => 'glyphicon glyphicon-info-sign', 
                'style' => 'float:right;',
                'data-toggle' => 'popover',
                'data-placement' => 'right',
                'data-content' => wfArray::get($value, 'info/text')
                ));
            $temp['script'] = wfDocument::createHtmlElement('script', " $(function () {  $('[data-toggle=\"popover\"]').popover()}) ");
            //$temp['script'] = wfDocument::createHtmlElement('script', "alert('8sdf\"');");            
          }
          
          
          $temp['input'] = wfDocument::createHtmlElement($type, $innerHTML, $attribute);
          $form_element[] = wfDocument::createHtmlElement('div', $temp, array(
                  'id' => 'div_'.$default['id'].'_'.$key, 
                  'class' => 'form-group '.wfArray::get($value, 'container_class'), 
                  'style' => wfArray::get($value, 'container_style')
                  ), array('class' => 'wf_form_row'));
        }
      }
    }
    $form_element[] = wfDocument::createHtmlElement('div', $buttons, array('class' => 'wf_form_row'));
    $form_attribute = array('id' => $default['id'], 'method' => 'post', 'role' => 'form');
    if(!$default['post_to_divzzz']){
      //$form_attribute['action'] = $default['url'];
    }
    if(!$default['ajax']){
      $form_attribute['action'] = $default['url'];
    }
    $form = wfDocument::createHtmlElement('form', $form_element, $form_attribute);
    
    
    //$script = wfDocument::createHtmlElement('script', "console.log(document.getElementById('".$default['id']."').parentNode.className);");
    
    // Check if form is render in Bootstrap Modal. If so we move save button to modal footer.
    $script = wfDocument::createHtmlElement('script', "if(document.getElementById('".$default['id']."').parentNode.className=='modal-body'){document.getElementById(document.getElementById('".$default['id']."').parentNode.id.replace('_body', '_footer')).appendChild(document.getElementById('".$default['id']."_save'));}");
    
    //wfHelp::yml_dump($form);
    
    wfDocument::renderElement(array($form, $script));
    
  }
  
  /**
   * Capture post from form via ajax.
   * @param type $data
   */
  public static function widget_capture($data){
    wfPlugin::includeonce('wf/array');
    $json = new PluginWfArray();
    //if(wfRequest::isPost()){
    if(true){
      $form = new PluginWfArray($data['data']);
      $form->set(null, PluginWfForm::bindAndValidate($form->get()));
      $json->set('success', false);
      $json->set('uid', wfCrypt::getUid());
      if($form->get('is_valid')){
        if($form->get('capture/plugin') && $form->get('capture/method')){
          $json->set('script', PluginWfForm::runCaptureMethod($form->get('capture/plugin'), $form->get('capture/method'), $form));
        }else{
          $json->set('script', array("alert(\"Param capture is missing in form data!\");"));
        }
      }else{
        $json->set('script', array("alert(\"".PluginWfForm::getErrors($form->get(), "\\n")."\");"));
      }
    }
    exit(json_encode($json->get()));
  }
  
  
  /**
   * Bind request params to form.
   * @param type $form
   * @return boolean
   */
  public static function bind($form, $preserve_default = false){
    foreach ($form['items'] as $key => $value) {
      $str = wfRequest::get($key);
      if($form['items'][$key]['type']=='checkbox'){
        if($str=='on'){$str=true;}
      }
      $form['items'][$key]['post_value'] = $str;
      if(!$preserve_default){
        $form['items'][$key]['default'] = $str;
      }
    }
    return $form;
  }
  
  /**
   * Bind array where keys matching keys in form.
   */
  public static function setDefaultsFromArray($form, $array){
    foreach ($form['items'] as $key => $value) {
      if(isset($array[$key])){
        $form['items'][$key]['default'] = $array[$key];
      }
    }
    return $form;
  }
  
  
  /**
   * Validate form.
   * @param type $form
   * @return type
   */
  public static function validate($form){
    //Validate mandatory.
    foreach ($form['items'] as $key => $value) {
        if(isset($value['mandatory']) && $value['mandatory']){
            if(strlen($value['post_value'])){
                $form['items'][$key]['is_valid'] = true;
            }else{
                $form['items'][$key]['is_valid'] = false;
                $form['items'][$key]['errors'][] = __('?lable is empty.', array('?lable' => $form['items'][$key]['lable']));
            }
        }else{
            $form['items'][$key]['is_valid'] = true;
        }
    }
    
    //Validate email.
    foreach ($form['items'] as $key => $value) {
        if($value['is_valid']){
            if(isset($value['validate_as_email']) && $value['validate_as_email']){
                if (!filter_var($value['post_value'], FILTER_VALIDATE_EMAIL)) {
                    // invalid emailaddress
                    //$form['items'][$key]['is_valid_text'] = 'Epost är felaktig!';
                    //$form['items'][$key]['errors'][] = __($form['items'][$key]['lable'].' is not correct as email.');
                    $form['items'][$key]['errors'][] = __('?lable is not an email.', array('?lable' => $form['items'][$key]['lable']));
                    $form['items'][$key]['is_valid'] = false;
                }                
            }
        }
    }

    //Validate php code injection.
    foreach ($form['items'] as $key => $value) {
      if($value['is_valid']){
        if (strstr($value['post_value'], '<?php') || strstr($value['post_value'], '?>')) {
            $form['items'][$key]['errors'][] = __('?lable has illegal character.', array('?lable' => $form['items'][$key]['lable']));
            $form['items'][$key]['is_valid'] = false;
        }                
      }
    }
    
    
    
    // Validator
    foreach ($form['items'] as $key => $value) {
      if(wfArray::get($value, 'validator')){
        foreach (wfArray::get($value, 'validator') as $key2 => $value2) {
          wfPlugin::includeonce($value2['plugin']);
          $obj = wfSettings::getPluginObj($value2['plugin']);
          $method = $value2['method'];
          if(wfArray::get($value2, 'data')){
            $form = $obj->$method($key, $form, wfArray::get($value2, 'data'));
          }else{
            $form = $obj->$method($key, $form);
          }
        }
      }
    }
    
    
    
    //Set form is_valid.
    $form['is_valid'] = true;
    foreach ($form['items'] as $key => $value) {
        if(!$value['is_valid']){
            $form['is_valid'] = false;
            $form['errors'][] = __('The form does not pass validation.');
            break;
        }
    }
    
    return $form;
  }
  
  public static function bindAndValidate($form){
//      $form = wfForm::bind($form);
//      $form = wfForm::validate($form);
      $form = self::bind($form);
      $form = self::validate($form);
    return $form;
  }
  
  
  public static function setErrorField($form, $field, $message){
    $form['is_valid'] = false;
    $form['items'][$field]['is_valid'] = false;
    $form['items'][$field]['errors'][] = $message;
    return $form;
  }
  
  public static function getErrors($form, $nl = '<br>'){
    $errors = null;
    if(isset($form['errors'])){
      foreach ($form['errors'] as $key => $value){
        $errors .= $value.$nl;
      }
    }
    foreach ($form['items'] as $key => $value) {
      if(!$value['is_valid']){
        foreach ($value['errors'] as $key2 => $value2){
          $errors .= '- '.$value2.$nl;
        }
      }
    }
    return $errors;
  }
  
  public function validate_email($field, $form, $data = array()){
    
    //wfHelp::yml_dump($data);
    
    if(wfArray::get($form, "items/$field/is_valid")){
      if (!filter_var(wfArray::get($form, "items/$field/post_value"), FILTER_VALIDATE_EMAIL)) {
        $form = wfArray::set($form, "items/$field/is_valid", false);
        $form = wfArray::set($form, "items/$field/errors/", __('?lable is not validated as email!', array('?lable' => wfArray::get($form, "items/$field/lable"))));
      }
    }
    return $form;
  }
  
  public function validate_password($field, $form, $data = array()){
    if(wfArray::get($form, "items/$field/is_valid")){
      $validate = $this->validatePasswordAbcdef09(wfArray::get($form, "items/$field/post_value"));
      if (!wfArray::get($validate, 'success')) {
        $form = wfArray::set($form, "items/$field/is_valid", false);
        $form = wfArray::set($form, "items/$field/errors/", __('?lable must have at lest one uppercase, lowercase, number and a minimum length of 8!', array('?lable' => wfArray::get($form, "items/$field/lable"))));
      }
    }
    return $form;
  }
  
  private function validatePasswordAbcdef09($password, $settings = array()) {
    // '$\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])(?=\S*[\W])\S*$';
    $data = array(
      'password' => $password,
      'settings' => $settings,
      'success' => false,
      'item' => array(
        'length' => array(
          'default' => '8',
          'match' => '(?=\S{[length],})',
          'result' => 2,
          'default_with_settings' => null 
        ),
        'lower_case' => array(
          'default' => true,
          'match' => '(?=\S*[a-z])',
          'result' => 2,
          'default_with_settings' => null 
        ),
        'upper_case' => array(
          'default' => true,
          'match' => '(?=\S*[A-Z])',
          'result' => 2,
          'default_with_settings' => null 
        ),
        'digit' => array(
          'default' => true,
          'match' => '(?=\S*[\d])',
          'result' => 2,
          'default_with_settings' => null 
        ),
        'special_character' => array(
          'default' => false,
          'match' => '(?=\S*[\W])',
          'result' => 2,
          'default_with_settings' => null 
        ),
      ),
      'match' => null
    );
    foreach ($data['item'] as $key => $value) {
      if(isset($data['settings'][$key])){
        $data['item'][$key]['default_with_settings'] = $data['settings'][$key];
      }else{
        $data['item'][$key]['default_with_settings'] = $data['item'][$key]['default'];
      }
    }
    if($data['item']['length']['default_with_settings']){
      // Replace length tag.
      $data['item']['length']['match'] = str_replace('[length]', $data['item']['length']['default_with_settings'], $data['item']['length']['match']);
    }
    $data['match'] = '$\S*';
    foreach ($data['item'] as $key => $value) {
      if($data['item'][$key]['default_with_settings']){
        $data['match'] .= $data['item'][$key]['match'];
        $data['item'][$key]['result'] = preg_match('$\S*'.$data['item'][$key]['match'].'\S*$', $data['password']);
      }
    }
    $data['match'] .= '\S*$';
    if (preg_match($data['match'], $data['password'])){
      $data['success'] = true;
    }
    return $data;
  }
  public function validate_equal($field, $form, $data = array('value' => 'some value')){
    if(wfArray::get($form, "items/$field/is_valid")){
      if (wfArray::get($form, "items/$field/post_value") != wfArray::get($data, 'value')) {
        $form = wfArray::set($form, "items/$field/is_valid", false);
        $form = wfArray::set($form, "items/$field/errors/", __('?lable is not equal to expected value!', array('?lable' => wfArray::get($form, "items/$field/lable"))));
      }
    }
    return $form;
  }


  public function validate_date($field, $form, $data = array()){
    if(wfArray::get($form, "items/$field/is_valid")){
      if (!PluginWfForm::isDate(wfArray::get($form, "items/$field/post_value"))){
        $form = wfArray::set($form, "items/$field/is_valid", false);
        $form = wfArray::set($form, "items/$field/errors/", __('?lable is not a date!', array('?lable' => wfArray::get($form, "items/$field/lable"))));
      }
    }
    return $form;
  }
  
  public static function isDate($value){
    if(strtotime($value)){
      $format_datetime = 'Y-m-d H:i:s';
      $format_date = 'Y-m-d';
      $format_date = wfDate::format();
      $d = DateTime::createFromFormat($format_datetime, $value);
      if($d && $d->format($format_datetime) == $value){
        return true;
      }else{
        $d = DateTime::createFromFormat($format_date, $value);
        if($d && $d->format($format_date) == $value){
          return true;
        }else{
          return false;
        }
      }
    }else{
      return false;
    }
  }

  
  public function validate_numeric($field, $form, $data = array()){
    wfPlugin::includeonce('wf/array');
//    var_dump(wfArray::get($form, "items/$field/post_value"));
//    exit;
    
    //wfHelp::yml_dump($form);
    
    $default = array('min' => 0, 'max' => 999999);
    $data = new PluginWfArray(array_merge($default, $data));
    
    //wfHelp::yml_dump($data->get(), true);
    
    if(wfArray::get($form, "items/$field/is_valid") && strlen(wfArray::get($form, "items/$field/post_value"))){
      if (!is_numeric(wfArray::get($form, "items/$field/post_value"))) {
        $form = wfArray::set($form, "items/$field/is_valid", false);
        $form = wfArray::set($form, "items/$field/errors/", __('?lable is not numeric!', array('?lable' => wfArray::get($form, "items/$field/lable"))));
      }else{
        if(
                (int)wfArray::get($form, "items/$field/post_value") < (int)$data->get('min') || 
                (int)wfArray::get($form, "items/$field/post_value") > (int)$data->get('max')
                ){
        $form = wfArray::set($form, "items/$field/is_valid", false);
        $form = wfArray::set($form, "items/$field/errors/", __('?lable must be between ?min and ?max!', array(
          '?lable' => wfArray::get($form, "items/$field/lable"),
          '?min' => $data->get('min'),
          '?max' => $data->get('max')
          )));
        }
      }
    }
    
    return $form;
  }
  
  public static function saveToYml($form){
    wfPlugin::includeonce('wf/array');
    wfPlugin::includeonce('wf/yml');
    
    $form = new PluginWfArray($form);
    
    //wfHelp::yml_dump($form->get(), true);
    
    if($form->get('yml/file') && $form->get('yml/path_to_key') && $form->get('items')){
      
      $yml = new PluginWfYml($form->get('yml/file'), $form->get('yml/path_to_key'));
      //wfHelp::yml_dump($yml->get());
      foreach ($form->get('items') as $key => $value) {
        $yml->set($key, wfArray::get($value, 'post_value'));
      }
      $yml->save();
      //wfHelp::yml_dump($yml->get());
      
      return true;
    }else{
      return false;
    }
    
    
    return false;
  }
  
  public static function runCaptureMethod($plugin, $method, $form){
    wfPlugin::includeonce($plugin);
    $obj = wfSettings::getPluginObj($plugin);
    return $obj->$method($form);
  }
  
  public function test_capture(){
    return array("alert('PluginWfForm method test_capture was tested! Replace to another to proceed your work.')");
  }
  
}


