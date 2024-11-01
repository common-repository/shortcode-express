var SCM = {
    select2_opts: {
        width: '200px'
    }
};

(function($) {
    $(function() {

        // reload editor
        SCM.reload_selectbox = function() {
            $('#sce-select').select2('destroy');
            $('#sce-select').select2(SCM.select2_opts);
        }

        // initialize select2
        SCM.reload_selectbox();

        $('#sce-select').on('change', function() {
            SCM.shortcode_id = $(this).val();
            $('#shortcode-response').hide();
            SCM.editor.setValue('');
            var data = {
                method: 'load',
                action: 'shortcode_express',
                id: SCM.shortcode_id
            };
            if ('' == data.id) {
                $('#shortcode-area').hide();
            }
            else {
                $('#save-area').hide();
                $('#loading-area').show();
                $.post(ajaxurl, data, function(response) {
                    $('#shortcode-area').show();
                    $('#loading-area').hide();
                    $('#save-area').show();
                    SCM.editor.setValue(response.data);
                   $('#shortcode-response').html('<p> Use this shortcode in your pages, posts or widgets : <strong>[sce name="'+response.shortcode_name+'"]</strong></p>');
                   $('#shortcode-response').show();                   
                }, 'json');
            }
        });

        // View shortcode
        
       $('#view-shortcode').click(function() {
       var data = {
          method: 'view',
          action: 'shortcode_express',
          id: SCM.shortcode_id
         };
               $('#save-area').hide();
               $('#loading-area').show();
               $.post(ajaxurl, data, function(response) {
               $('#shortcode-area').show();
               $('#loading-area').hide();
               $('#save-area').show();
               var azerty = window.open("", "", "toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=500, height=300, top="+(screen.height-400)+", left="+(screen.width-840));
               azerty.document.write(response.data);
               azerty.document.title = response.shortcode_name;
               azerty.document.close();
               //SCM.editor.setValue(response.data);
               $('#shortcode-response').html('<p> Use this shortcode in your pages, posts or widgets : <strong>[sce name="'+response.shortcode_name+'"]</strong></p>');
               $('#shortcode-response').show();                   
               }, 'json');         
         $(function() {
         $( "#dialog" ).dialog();
         });
        });
        

        // initialize codemirror
        SCM.editor = CodeMirror.fromTextArea(document.getElementById('shortcode-content'), {
            mode: 'php',
            indentUnit: 4,
            lineNumbers: true
        });


        // add
        $('#add-shortcode').click(function() {
            var data = {
                method: 'add',
                action: 'shortcode_express',
                name: $('#shortcode-name').val()
            };

            if ('' == data.name) return;
            $.post(ajaxurl, data, function(response) {
                if ('ok' == response.status) {
                    SCM.editor.setValue('');
                    $('#shortcode-name').val('');
                    $('#sce-select').append('<option value="'+response.data.id+'">'+data.name+'</option>');
                    $('#sce-select').val(response.data.id);
                    SCM.reload_selectbox();
                    $('#shortcode-area').show();
                    SCM.shortcode_id = response.data.id;
                }
                $('#shortcode-response').html('<p>'+response.status_message+' : <strong>'+response.shortcode_name+'</strong></p>');
                $('#shortcode-response').show();
            }, 'json');
        });


        // edit
        $('#edit-shortcode').click(function() {
            var data = {
                method: 'edit',
                action: 'shortcode_express',
                id: SCM.shortcode_id,
                content: SCM.editor.getValue()
            };
            $.post(ajaxurl, data, function(response) {
                 $('#shortcode-response').html('<p>'+response.status_message+' : <strong>'+response.shortcode_name+'</strong></p>');
                $('#shortcode-response').show();
            }, 'json');
        });


        // delete
        $('#delete-shortcode').click(function() {
            if (confirm('Are you sure you want to delete this shortcode?')) {
                var data = {
                    method: 'delete',
                    action: 'shortcode_express',
                    id: SCM.shortcode_id
                };
                $.post(ajaxurl, data, function(response) {
                    $('#shortcode-area').hide();
                    $('#sce-select option[value='+SCM.shortcode_id+']').remove();
                    SCM.reload_selectbox();
                    $('#shortcode-response').html('<p>'+response.status_message+'</p>');
                    $('#shortcode-response').show();
                    SCM.shortcode_id = null;
                }, 'json');
            }
        });
    });
})(jQuery);