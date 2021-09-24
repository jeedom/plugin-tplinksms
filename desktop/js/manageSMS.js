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
$('body').on('newTPLinkSMS', function(_event, _options) {
  if (_options['type'] == 'inbox') {
    $('#div_alert').showAlert({message: _options['alert'], level: 'success'});
  }
  else {
    $('#div_alert').showAlert({message: _options['alert'], level: 'warning'});
  }
  refresh(_options['type'])
  $.hideAlert()
})

$('tbody').not('.buttons').on('click', 'td', function() {
  if (!$(this).hasClass('buttons')) {
    var checkbox = $(this).siblings().find('input[type="checkbox"]')
    checkbox.prop('checked', !checkbox.prop('checked')).trigger('change')
  }
})

$('tbody').on('change', 'input[type="checkbox"]', function() {
  var panel = $(this).closest('.panel')
  var checked = panel.find('input[type=checkbox]:checked')
  if (checked.length > 0) {
    panel.find('.panel-footer > a.btn-danger').removeClass('hidden')
    if (panel.attr('id') == 'inbox' && checked.closest('tr').hasClass('info')) {
      panel.find('.panel-footer > a.btn-primary').removeClass('hidden')
    }
    else {
      panel.find('.panel-footer > a.btn-primary').addClass('hidden')
    }
  }
  else {
    panel.find('.panel-footer > a.btn-danger').addClass('hidden')
    if (panel.attr('id') == 'inbox') {
      panel.find('.panel-footer > a.btn-primary').addClass('hidden')
    }
  }
})

$('#inbox, #outbox').on('click', '.btn-danger', function() {
  var panel = $(this).closest('.panel')
  if ($(this).parent().hasClass('buttons')) {
    var sms_order = $(this).closest('tr').attr('data-sms_order')
    bootbox.confirm('{{Êtes-vous sûr de vouloir supprimer le SMS}} '+ panel.attr('id')+'/'+sms_order + ' ?', function(result) {
      if (result) {
        deleteSMS(panel.attr('id')+'/'+sms_order)
        refresh(panel.attr('id'))
      }
    })
  }
  else {
    var checked = $('#'+panel.attr('id')).find('input[type=checkbox]:checked')
    bootbox.confirm('{{Êtes-vous sûr de vouloir supprimer}} '+ checked.length +' {{SMS}} ?', function(result) {
      if (result) {
        checked.each(function() {
          deleteSMS(panel.attr('id')+'/'+$(this).closest('tr').attr('data-sms_order'))
          $(this).prop('checked', false).trigger('change')
        })
        window.location.reload(true)
      }
    })
  }
})

$('#inbox').on('click', '.btn-primary', function() {
  if ($(this).parent().hasClass('buttons')) {
    markAsRead($(this).closest('tr').attr('data-sms_order'))
    $(this).addClass('hidden').closest('tr').removeClass('info')
  }
  else {
    $('#inbox').find('input[type=checkbox]:checked').each(function() {
      var tr = $(this).closest('tr')
      if (tr.hasClass('info')) {
        markAsRead(tr.attr('data-sms_order'))
        tr.removeClass('info').find('.btn-primary').addClass('hidden')
      }
      $(this).prop('checked', false).trigger('change')
    })
  }
})

function refresh(type = 'inbox') {
  $.ajax({
    type: "POST",
    url: "plugins/tplinksms/core/ajax/tplinksms.ajax.php",
    data: {
      action: "getSMS",
      type: type,
    },
    dataType: 'json',
    error: function (request, status, error) {
      handleAjaxError(request, status, error, $('#div_alert'));
    },
    success: function (data) {
      var tr = ''
      $.each(data.result, function(i) {
        tr += '<tr class="'+((data.result[i]['unread']) ? 'info' : '')+'" data-sms_order="'+(i+1)+'">'
        tr += '<td>'
        if (type == 'inbox') {
          tr += '<input type="checkbox">'
        }
        tr+='</td>'
        tr += '<td>'+data.result[i]['username']+'</td>'
        tr += '<td>'+data.result[i]['datetime']+'</td>'
        tr += '<td>'+data.result[i]['content']+'</td>'
        tr += '<td class="buttons">'
        if (type == 'inbox') {
          tr += '<a class="btn btn-sm btn-danger" title="{{Supprimer}}"><i class="fas fa-trash-alt"></i></a>'
          if (data.result[i]['unread']) {
            tr += '<a class="btn btn-sm btn-primary" title="{{Marquer comme lu}}"><i class="fas fa-envelope-open-text"></i></a>'
          }
        }
        tr += '</td>'
        tr +='</tr>'
      })
      $('#'+type).find('tbody').html(tr)
    }
  })
}

function markAsRead(_order) {
  $.ajax({
    type: "POST",
    url: "plugins/tplinksms/core/ajax/tplinksms.ajax.php",
    data: {
      action: "updateSMS",
      sms_order: _order,
    },
    dataType: 'json',
    error: function (request, status, error) {
      handleAjaxError(request, status, error, $('#div_alert'));
    }
  })
}

function deleteSMS(_url) {
  $.ajax({
    type: "POST",
    url: "plugins/tplinksms/core/ajax/tplinksms.ajax.php",
    data: {
      action: "deleteSMS",
      url: _url,
    },
    dataType: 'json',
    error: function (request, status, error) {
      handleAjaxError(request, status, error, $('#div_alert'));
    }
  })
}
