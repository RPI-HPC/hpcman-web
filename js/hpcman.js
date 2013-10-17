$(function() {
  $('.searchkey_principal').keypress(function (e) {
    if(e.which == 13) {
      $('input[name="type"]').val('principal');
      $('.searchkey_principal').val(ui.item.value);
      $(this).closest("form").submit();
    }
  });

  $('.searchkey_principal').autocomplete({
    source: 'json/get.php?q=partial-principal-list',
    minLength: 2,
    select: function(e, ui) {
      $('input[name="type"]').val('principal');
      $('.searchkey_principal').val(ui.item.value);
      $(this).closest("form").submit();
    }
  });

  $('.searchkey_account').keypress(function (e) {
    if(e.which == 13) {
      $('input[name="type"]').val('username');
      $('.searchkey_account').val(ui.item.value);
      $(this).closest("form").submit();
    }
  });

  $('.searchkey_account').autocomplete({
    source: 'json/get.php?q=partial-username-list',
    minLength: 2,
    select: function(e, ui) {
      $('input[name="type"]').val('username');
      $('.searchkey_account').val(ui.item.value);
      $(this).closest("form").submit();
    }
  });

  $('.searchkey_project').autocomplete({
    source: 'json/get.php?q=partial-project-list',
    minLength: 2,
    select: function(e, ui) {
      $('.searchkey_project').val(ui.item.value);
      $(this).closest("form").submit();
    }
  });

  function handleUsed(data, field) {
    if (data.used) {
      field.removeClass('field-good');
      field.addClass('field-bad');
    } else {
      field.removeClass('field-bad');
      field.addClass('field-good');
    }
  }

  function addUniqueField(field, func, min) {
    $('#'+field).keyup(function (e) {
      if ($(this).val().length == 0 || (typeof min !== 'undefined' && $(this).val().length < min)) {
        $(this).removeClass('field-bad');
        $(this).removeClass('field-good');
        return;
      }

      $.getJSON("json/get.php", {'q':func, 'term': $(this).val()}, function (data) {
        handleUsed(data, $("#"+field));
      });
    });
  }

  var unique_fields = [
    {'id':'emailaddress', 'function':'email-used', 'min':5},
    {'id':'defaultusername', 'function':'defaultusername-used', 'min':4},
    {'id':'projname', 'function':'projname-used', 'min':3}
  ];

  for (var i = 0; i < unique_fields.length; i++) {
    field = unique_fields[i];
    addUniqueField(field['id'], field['function'], field['min']);
  }

});
