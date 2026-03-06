jQuery(document).ready(
    function($){
        let PREFIX = "ect";
        var name = PREFIX+'_license_registration['+PREFIX+'-purchase-code]';
        var val = $("input[name='"+name+"']").val();
        var val_length =val.length;
        if(val_length!=0){
            var subStr = val.toString().substr(0, 9);
            var test = val.toString().substr(-9);
            var results = subStr+'-XXXXXXXX-XXXXXXXX-'+test;
             $("input[name='"+name+"']").attr('value',results);
        }
            $(".required [name='"+PREFIX+"_license_registration["+PREFIX+"-purchase-code]']").attr('required','required');
            $(".required [name='"+PREFIX+"_license_registration["+PREFIX+"-purchase-code]']").attr('oninvalid','this.setCustomValidity("Purchase code can not be empty!")');
            $(".required [name='"+PREFIX+"_license_registration["+PREFIX+"-purchase-code]']").attr('oninput','this.setCustomValidity("")');

            $(".required [name='"+PREFIX+"_license_registration["+PREFIX+"-client-emailid]']").attr('type','email');

            if( $("."+PREFIX+"_verification_enable").length > 0 ){
                $("#"+PREFIX+"-activation-button #submit").attr('disabled','disabled');
                $('#'+PREFIX+'-activation-button').addClass('hidden');
                $('#'+PREFIX+'-verify-permission').addClass('hidden');
                $('.'+PREFIX+'-notice-red:not(".uninstall")').addClass('hidden');
            }else{
                $("#"+PREFIX+"-uninstall-license").attr('disabled','disabled');
                $('#'+PREFIX+'-deactivation-button').addClass('hidden');
            }
    
            // product uninstall hook
            $("a#"+PREFIX+"-uninstall-license").on('click',function(e){
                e.preventDefault();
                if( $("a#"+PREFIX+"-uninstall-license").attr('disabled') ){
                    return;
                }
                let code = $( '[id*="'+PREFIX+'-nonce-code"]').val();
                $.ajax({
                  type: "POST",
                  dataType: "json",
                  url: ajax_object.ajax_url,
                  data: {
                        action:PREFIX+'_uninstall_license',
                        '_password':ajax_object.verify
                        },
                beforeSend: function(data){
                    $("a#"+PREFIX+"-uninstall-license").html("Uninstalling license... <span class='cool_timeline-loading'></span>");
                },
                success: function (response) {
                        $("a#cool_timeline-uninstall-license").html("License Uninstalled!");
                        alert( response.Message );
                        window.location.reload();
                  }
              })
    
            });
    
            var request = $('#ccpa_plugin_support').value;
            $("#cool_timeline_support_btn").on('click',function(){
            $.ajax({
              type: "POST",
              dataType: "json",
              url: ajax_object.ajax_url,
              data: {action:PREFIX+'_submit_ticket',
                    request:request
                    },
              success: function (response) {                  
                          console.log(response)
              }
          })
          });
    });