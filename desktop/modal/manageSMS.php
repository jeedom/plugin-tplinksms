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
if (!isConnect('admin')) {
  throw new Exception('{{401 - Accès non autorisé}}');
}
include_file('desktop', 'manageSMS', 'css', 'tplinksms');
$router = tplinksms::getRouter();
?>
<div id="tplinksms-body">
  <div class="panel panel-default text-center col-xs-12 col-lg-6">
    <div class="panel-heading">
      <h1 class="panel-title router-title">
        <a href="index.php?v=d&m=tplinksms&p=tplinksms&id=<?= $router->getId() ?>"><?= $router->getName() ?></a>
      </h1>
    </div>
    <div class="panel-body no-padding">
      <div class="col-sm-4">
        <?php if ($router->getConfiguration('model', '') != '') : ?>
          <img src="/plugins/tplinksms/core/config/images/<?=$router->getConfiguration('model')?>.png" width="150">
        <?php endif ?>
      </div>
      <div class="col-sm-8">
        <table class="table table-condensed">
          <caption><i class="fas fa-users"></i> {{Contacts enregistrés}}</caption>
          <thead>
            <tr>
              <th class="text-center">{{Nom}}</th>
              <th class="text-center">{{Profil}}</th>
              <th class="text-center">{{Numéro}}</th>
              <th class="text-center">{{Interactions}}</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($router->getCmd('action') as $cmd) {
              if ($cmd->getSubType() == 'message') {
                echo '<tr data-cmd_id="'.$cmd->getId().'">';
                echo '<td>'.$cmd->getName().'</td>';
                echo '<td>'.(($cmd->getConfiguration('user', '') != '') ? user::byId($cmd->getConfiguration('user', ''))->getLogin() : '').'</td>';
                echo '<td>'.$cmd->getConfiguration('phonenumber', '').'</td>';
                echo '<td><input type="checkbox" checked="'.$cmd->getConfiguration('interactions').'" disabled></td>';
                echo '</tr>';
              }
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <hr class="col-xs-12">
  <div class="alert alert-info col-xs-12 col-lg-6 text-center">
    {{Seuls les 8 derniers SMS reçus ou envoyés sont consultables}}
  </div>
  <div class="col-xs-12"></div>
  <div class="panel panel-success col-xs-12 col-lg-5 no-padding" id="inbox">
    <div class="panel-heading">
      <h3 class="panel-title">
        <i class="fas fa-sign-in-alt"></i>
        {{Boite de réception}}</h3>
      </div>
      <table class="table table-hover">
        <thead>
          <tr>
            <th></th>
            <th>{{Expéditeur}}</th>
            <th>{{Date}}</th>
            <th>{{Message}}</th>
            <th>{{Actions}}</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($router->callApi('inbox', 'GET', ['all'=>true]) as $i=>$smsin) {
            echo '<tr class="'.(($smsin['unread']) ? 'info' : '').'" data-sms_order="'.($i+1).'">';
            echo '<td><input type="checkbox"></td>';
            echo '<td>'.$smsin['username'].'</td>';
            echo '<td>'.$smsin['datetime'].'</td>';
            echo '<td>'.$smsin['content'].'</td>';
            echo '<td class="buttons">';
            echo '<a class="btn btn-sm btn-danger" title="{{Supprimer}}"><i class="fas fa-trash-alt"></i></a>';
            if ($smsin['unread']) {
              echo '<a class="btn btn-sm btn-primary" title="{{Marquer comme lu}}"><i class="fas fa-envelope-open-text"></i></a>';
            }
            echo '</td>';
            echo '</tr>';
          } ?>
        </tbody>
      </table>
      <div class="panel-footer">
        <a class="btn btn-sm btn-danger hidden"><i class="fas fa-trash-alt"></i>  {{Supprimer la sélection}}</a>
        <a class="btn btn-sm btn-primary hidden"><i class="fas fa-envelope-open-text"></i> {{Marquer comme lu(s)}}</a>
      </div>
    </div>

    <div class="panel panel-warning col-xs-12 col-lg-5 no-padding" id="outbox">
      <div class="panel-heading">
        <h3 class="panel-title">
          <i class="fas fa-sign-out-alt"></i>
          {{Boite d'envoi}}</h3>
        </div>
        <table class="table table-hover">
          <thead>
            <tr>
              <th></th>
              <th>{{Destinataire}}</th>
              <th>{{Date}}</th>
              <th>{{Message}}</th>
              <!-- <th>{{Actions}}</th> -->
            </tr>
          </thead>
          <tbody>
            <?php foreach ($router->callApi('outbox', 'GET', ['all'=>true]) as $i=>$smsout) {
              echo '<tr data-sms_order="'.($i+1).'">';
              echo '<td></td>';
              echo '<td>'.$smsout['username'].'</td>';
              echo '<td>'.$smsout['datetime'].'</td>';
              echo '<td>'.$smsout['content'].'</td>';
              // echo '<td class="buttons">';
              // echo '<a class="btn btn-sm btn-danger" title="{{Supprimer}}"><i class="fas fa-trash-alt"></i></a>';
              // echo '</td>';
              echo '</tr>';
            } ?>
          </tbody>
        </table>
        <div class="panel-footer">
          <!-- <a class="btn btn-sm btn-danger hidden"><i class="fas fa-trash-alt"></i> {{Supprimer la sélection}}</a> -->
        </div>
      </div>
    </div>

    <?php include_file('desktop', 'manageSMS', 'js', 'tplinksms');?>
