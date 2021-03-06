<?php

/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class tplinksms extends eqLogic {

  public static function dependancy_info() {
    $return = array();
    $return['progress_file'] = jeedom::getTmpFolder(__CLASS__) . '/dependance';
    $return['state'] = 'ok';
    if (config::byKey('lastDependancyInstallTime', __CLASS__) == '') {
      $return['state'] = 'nok';
    } else if (!file_exists(__DIR__ . '/../../resources/tplinksmsd/node_modules')) {
      $return['state'] = 'nok';
    }
    return $return;
  }

  public static function dependancy_install() {
    log::remove(__CLASS__ . '_dep');
    return array('script' => dirname(__FILE__) . '/../../resources/install_#stype#.sh ' . jeedom::getTmpFolder(__CLASS__) . '/dependance', 'log' => log::getPathToLog(__CLASS__ . '_dep'));
  }

  public static function deamon_info() {
    $return = array();
    $return['log'] = __CLASS__;
    $return['launchable'] = 'ok';
    $return['state'] = 'nok';
    if (!self::getPassword()) {
      $return['launchable'] = 'nok';
      $return['launchable_message'] = __('Veuillez renseigner le mot de passe de connexion au routeur', __FILE__);
    }
    $cron = cron::byClassAndFunction(__CLASS__, 'daemon');
    if (self::isRunningApi() && is_object($cron) && $cron->running()) {
      $return['state'] = 'ok';
    }
    return $return;
  }

  public static function deamon_start() {
    $tplinksms_path = realpath(dirname(__FILE__) . '/../../resources/tplinksmsd');
    chdir($tplinksms_path);
    $cmd = 'sudo /usr/bin/node ' . $tplinksms_path . '/api-bridge.js';
    log::add(__CLASS__, 'info', __('D??marrage du d??mon TPLink SMS', __FILE__) . ' ' . $cmd);
    exec($cmd . ' >> ' . log::getPathToLog(__CLASS__) . ' 2>&1 &');
    sleep(2);
    if (!self::callApi()) {
      system::kill('api-bridge.js');
      throw new Exception(__('Veuillez v??rifier les informations de connexion au routeur', __FILE__));
    } else {
      $cron = cron::byClassAndFunction(__CLASS__, 'daemon');
      if (!is_object($cron)) {
        $cron = new cron();
        $cron->setClass(__CLASS__)
          ->setFunction('daemon')
          ->setEnable(1)
          ->setDeamon(1)
          ->setDeamonSleepTime(2)
          ->setTimeout(1440)
          ->setSchedule('* * * * *')
          ->save();
      }
      $cron->run();
    }
    $i = 0;
    while ($i < 30) {
      $deamon_info = self::deamon_info();
      if ($deamon_info['state'] == 'ok') {
        break;
      }
      sleep(1);
      $i++;
    }
    if ($i >= 30) {
      log::add(__CLASS__, 'error', 'Impossible de d??marrer le d??mon, v??rifiez le log', 'unableStartDeamon');
      system::kill('api-bridge.js');
      return false;
    }
    message::removeAll(__CLASS__, 'unableStartDeamon');
    return true;
  }

  public static function deamon_stop() {
    log::add(__CLASS__, 'info', __('Arr??t du d??mon TPLink SMS', __FILE__));
    $cron = cron::byClassAndFunction(__CLASS__, 'daemon');
    if (is_object($cron)) {
      $cron->halt();
    }
    system::kill('api-bridge.js');
  }

  public static function daemon() {
    $router = self::getRouter();
    if ($router->getIsEnable()) {
      $router->poll();
    }
  }

  public static function postConfig_ip() {
    self::setJsonConfig();
  }
  public static function postConfig_password() {
    self::setJsonConfig();
  }

  public static function getRouter() {
    if (!is_object($router = self::byLogicalId('router', __CLASS__))) {
      $router = new tplinksms();
      $router->setEqType_name(__CLASS__)
        ->setLogicalId('router')
        ->setName(__('TPLink SMS', __FILE__))
        ->setIsEnable(1)
        ->setIsVisible(1)
        ->save();
    }
    return $router;
  }

  public function setJsonConfig() {
    if (self::getPassword()) {
      $jsonConf = json_encode(
        [
          'url' => 'http://' . config::byKey('ip', __CLASS__, '192.168.1.1'),
          'login' => 'admin',
          'password' => self::getPassword(),
          'api_listen_host' => '127.0.0.1',
          'api_listen_port' => 3000,
          'api_users' => ['jeedom' => 'Mjeedom96']
        ]
      );
      file_put_contents(__DIR__ . '/../../resources/tplinksmsd/config.json', $jsonConf);
    }
  }

  public function postSave() {
    $lastReceived = $this->getCmd('info', 'last_received');
    if (!is_object($lastReceived)) {
      $lastReceived = (new tplinksmsCmd)
        ->setName(__('Dernier SMS re??u', __FILE__))
        ->setEqLogic_id($this->getId())
        ->setLogicalId('last_received')
        ->setIsVisible(0);
    }
    $lastReceived->setType('info')
      ->setSubType('string')
      ->save();

    $lastFrom = $this->getCmd('info', 'last_from');
    if (!is_object($lastFrom)) {
      $lastFrom = (new tplinksmsCmd)
        ->setName(__('Dernier exp??diteur', __FILE__))
        ->setEqLogic_id($this->getId())
        ->setLogicalId('last_from')
        ->setIsVisible(0);
    }
    $lastFrom->setType('info')
      ->setSubType('string')
      ->save();

    $lastSent = $this->getCmd('info', 'last_sent');
    if (!is_object($lastSent)) {
      $lastSent = (new tplinksmsCmd)
        ->setName(__('Dernier SMS envoy??', __FILE__))
        ->setEqLogic_id($this->getId())
        ->setLogicalId('last_sent')
        ->setIsVisible(0);
    }
    $lastSent->setType('info')
      ->setSubType('string')
      ->save();

    $lastTo = $this->getCmd('info', 'last_to');
    if (!is_object($lastTo)) {
      $lastTo = (new tplinksmsCmd)
        ->setName(__('Dernier destinataire', __FILE__))
        ->setEqLogic_id($this->getId())
        ->setLogicalId('last_to')
        ->setIsVisible(0);
    }
    $lastTo->setType('info')
      ->setSubType('string')
      ->save();

    // $sendMessage = $this->getCmd('action', 'send_sms');
    // if (!is_object($sendMessage)) {
    //   $sendMessage = (new tplinksmsCmd)
    //   ->setName(__('Envoyer SMS', __FILE__))
    //   ->setEqLogic_id($this->getId())
    //   ->setLogicalId('send_sms')
    //   ->setIsVisible(0);
    // }
    // $sendMessage->setType('action')
    // ->setSubType('message')
    // ->setDisplay('title_placeholder', __('Num??ro de t??l??phone', __FILE__))
    // ->setDisplay('message_placeholder', __('Message', __FILE__))
    // ->save();
  }

  public function poll() {
    $inbox = self::callApi();
    $lastReceived = $this->getCmd('info', 'last_received');
    if ($inbox['index'] > $lastReceived->getConfiguration('last_index', -1)) {
      log::add(__CLASS__, 'debug', __('Nouveau SMS dans la boite de r??ception', __FILE__) . ' : ' . print_r($inbox, true));
      event::add('newTPLinkSMS', array('type' => 'inbox', 'alert' => __('Nouveau SMS re??u de', __FILE__) . ' ' . $inbox['username']));
      $lastReceived->setConfiguration('last_index', $inbox['index'])->save();
      if (is_object($senderCmd = $this->isRegisteredNumber('0' . substr($inbox['from'], -9)))) {
        if (!$senderCmd->askResponse($inbox['content']) && $senderCmd->getConfiguration('interactions', 0) == 1) {
          $params = array(
            'plugin' => __CLASS__,
            'profile' => $inbox['username'],
            'reply_cmd' => $senderCmd,
            'force_reply_cmd' => 1
          );
          $reply = interactQuery::tryToReply($inbox['content'], $params);
          if (is_array($reply)) {
            $senderCmd->execute(['message' => $reply['reply']]);
          }
        }
      }
      $this->checkAndUpdateCmd('last_from', $inbox['username'], $inbox['datetime']);
      $lastReceived->event($inbox['content'], $inbox['datetime']);
    }

    $outbox = self::callApi('outbox');
    $lastSent = $this->getCmd('info', 'last_sent');
    if ($outbox['index'] > $lastSent->getConfiguration('last_index', -1)) {
      log::add(__CLASS__, 'debug', __('Nouveau SMS dans la boite d\'envoi', __FILE__) . ' : ' . print_r($outbox, true));
      event::add('newTPLinkSMS', array('type' => 'outbox', 'alert' => __('Nouveau SMS envoy?? ??', __FILE__) . ' ' . $outbox['username']));
      $lastSent->setConfiguration('last_index', $outbox['index'])->save();
      $this->checkAndUpdateCmd('last_to', $inbox['username'], $inbox['datetime']);
      $lastSent->event($outbox['content'], $inbox['datetime']);
    }
  }

  public static function callApi($_url = 'inbox', $_request = 'GET', $_options = array()) {
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'http://127.0.0.1:3000/api/v1/sms/' . $_url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_USERPWD => 'jeedom:Mjeedom96',
      CURLOPT_CUSTOMREQUEST => $_request,
      CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'accept: application/json'
      )
    ));
    if ($_request == 'POST') {
      curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(['content' => $_options['message'], 'to' => $_options['to']]));
    }
    $response = curl_exec($curl);
    curl_close($curl);
    $result = json_decode($response, true);

    if (!is_array($result) || $result['status'] != 200) {
      log::add(__CLASS__, 'warning', __('Erreur de connexion au routeur', __FILE__) . ' ' . print_r($result, true));
      return false;
    } else if ($_request == 'GET') {
      if (isset($_options['all'])) {
        foreach ($result['data'] as $i => $sms) {
          $result['data'][$i] = self::formatSMS($sms, $_url);
        }
        return $result['data'];
      }
      return self::formatSMS($result['data'][0], $_url);
    } else if ($_request == 'PATCH') {
      log::add(__CLASS__, 'debug', __('SMS marqu?? comme lu', __FILE__) . ' : ' . $_url);
    } else if ($_request == 'DELETE') {
      log::add(__CLASS__, 'debug', __('SMS supprim??', __FILE__) . ' : ' . $_url);
    }
  }

  public static function formatSMS($_sms = array(), $_type = 'inbox') {
    $return = array(
      'index' => $_sms['index'],
      'content' => trim($_sms['content'])
    );
    if ($_type == 'inbox') {
      $return['username'] = self::getRouter()->getSender('0' . substr($_sms['from'], -9));
      $return['datetime'] = date('Y-m-d H:i:s', strtotime($_sms['receivedTime']));
      $return['from'] = $_sms['from'];
      $return['unread'] = $_sms['unread'];
    } else {
      $return['username'] = self::getRouter()->getSender('0' . substr($_sms['to'], -9));
      $return['datetime'] = date('Y-m-d H:i:s', strtotime($_sms['sendTime']));
    }
    return $return;
  }

  private function isRegisteredNumber($_phoneNumber = '') {
    if (is_object($cmd = $this->searchCmdByConfiguration($_phoneNumber, 'action')[0])) {
      return $cmd;
    }
    return false;
  }

  public static function isRunningApi() {
    if (!empty(system::ps('api-bridge.js'))) {
      return true;
    }
    return false;
  }

  public function getSender($_phoneNumber = '') {
    if (is_object($senderCmd = $this->isRegisteredNumber($_phoneNumber))) {
      if (($senderId = $senderCmd->getConfiguration('user', '')) != '') {
        return user::byId($senderId)->getLogin();
      } else {
        return $senderCmd->getName();
      }
    }
    return $_phoneNumber;
  }

  public function getPassword() {
    return config::byKey('password', __CLASS__, false);
  }
}

class tplinksmsCmd extends cmd {

  public function dontRemoveCmd() {
    if ($this->getType() == 'info') {
      return true;
    }
    return false;
  }

  public function preSave() {
    if ($this->getSubtype() == 'message') {
      $phoneNumber = '0' . substr($this->getConfiguration('phonenumber'), -9);
      if (strlen($phoneNumber) != 10 || !is_numeric($phoneNumber)) {
        throw new Exception(__('Veuillez renseigner un num??ro de t??l??phone valide', __FILE__));
      }
      $this->setConfiguration('phonenumber', $phoneNumber)
        ->setDisplay('title_disable', 1);
    }
  }

  public function execute($_options = array()) {
    if (isset($_options['message'])) {
      tplinksms::callApi('outbox', 'POST', ['message' => trim($_options['message']), 'to' => $this->getConfiguration('phonenumber')]);
    }
  }
}
