(function ($) {
    
  $(".ect-advance-list").each(function(i){
    var advance_list_id = $(this).data("id");
    let ect_table_btnNext = $('.ect-adl-nxt'+advance_list_id).text();
    let ect_table_btnprev = $('.ect-adl-prev'+advance_list_id).text();
    let ect_evText = $('.ect-adl-text'+advance_list_id).text();
    let ect_InTottal = $('.ect-adl-intottal'+advance_list_id).text();
    let ect_tabel_serach = $('.ect-adl-search'+advance_list_id).text();
    $(this).DataTable({
        'dom': "rftp",
        'pageLength': 10,
        'paging': true,
        'order': [], // No sorting on initial load
        'autoWidth': false,
        'responsive': true,
        'columnDefs': [
            { 'responsivePriority': 1, 'targets': 2 }
        ],
        // 'rowReorder': true,
        'columnDefs': [
          {
            'sortable': false,
            'targets': ["ect-img", "ect-view-more"],
          },
          {
            "targets": ['tag-column','ect-cattag-hide'],
            "className": 'ect-cattag-hide'
          }
        ],
        "fnInfoCallback": function (oSettings, iStart, iEnd, iMax, iTotal, sPre) {
          return iEnd +' '+ect_evText+'( '+ iTotal+ ' '+ect_InTottal+')';
      },
      "fnDrawCallback": function() {
        if (Math.ceil((this.fnSettings().fnRecordsDisplay()) / this.fnSettings()._iDisplayLength) > 1) {
        $('.dataTables_paginate').css("display", "block");
        } else {
        $('.dataTables_paginate').css("display", "none");
        }
       },
      'language': {
              "search": "" ,
              "searchPlaceholder":ect_tabel_serach,
              "paginate": 
              {
                  "next": ect_table_btnNext,
                  "previous": ect_table_btnprev,
              },
      }
      });
      
      var ect_table_clone = $(this).DataTable();
      $(".dataTables_length").append($("#ect-table-List_info"));
      $("#ect-table-List"+advance_list_id+"_filter").prepend($('#ect-category-filter'+advance_list_id));
      var ect_categoryIndex = 0 ;
      var ect_tagIndex = 0 ;
        $("#ect-table-List"+advance_list_id+" th").each(function (i) {
                    if ($(this).data('catfilter') == "Category") {
                        ect_categoryIndex = i;  
                    }
                    if ($(this).html() == "Tags") {
                        ect_tagIndex = i;  
                    } 
        });
        $.fn.dataTable.ext.search.push(
                    function (settings, data, dataIndex) {
                        if($('#ect-cat-filter'+advance_list_id).length){
                            var ectbe_selectCat = $('#ect-cat-filter'+advance_list_id).val();
                            var ectbe_category = data[ect_categoryIndex];
                            if (ectbe_selectCat == 'All') {
                                return true;
                            }
                            if (ectbe_selectCat == " " || ectbe_category.toLowerCase().includes(ectbe_selectCat.toLowerCase())) {
                                return true;
                            }
                        }
                        else{
                            return true;
                        }
  
                    return false;
        });
  
        $('#ect-cat-filter'+advance_list_id).change(function (e) {
            ect_table_clone.draw();
        });
  
        $.fn.dataTable.ext.search.push(
                function (settings, data, dataIndex) {
                    if($('#ect-tagFilter'+advance_list_id).length){
                        var ect_selected_Tag = $('#ect-tagFilter'+advance_list_id).val();
                        var ectbe_tag = data[ect_tagIndex];
                        if (ect_selected_Tag === 'All') {
                            return true;
                        }
                        if(ect_selected_Tag === "" || ectbe_tag.toLowerCase().includes(ect_selected_Tag.toLowerCase())) {
                            return true;
                          }
                         }else{
                            return true;
                        }
                    return false;
                   
        });
        $("#ect-tagFilter"+advance_list_id).change(function (e) {
            ect_table_clone.draw();
        });
  
        $("#ect-refresh"+advance_list_id).on('click', function () {
            $('#ect-cat-filter'+advance_list_id).val('');
            $('#ect-tagFilter'+advance_list_id).val('');
            ect_table_clone.draw();
        });
  
    if($('.ect-cat-filter').length == 0 && $('.ect-tagFilter').length==0 ){
        $('.dataTables_filter').css({'display':'block','justify-content':''})
    }
  
    // // Table Scroll Bar 
    // $(".dataTables_wrapper").scroll(function(){ 
    //     $('.ect-advance-list-mobi-serial').css({
    //         "position":"sticky",
    //         "left":"-10px",
    //     }); 
    //     $('td.ect-advance-list-mobi-serial').css({
    //         "background-color":"inherit",
    //     }); 
    //     $('.dataTables_filter').css({
    //         "position":"sticky",
    //         "top":"0",
    //         "left":"0",
    //     }); 
    //     $('.dataTables_length, .dataTables_paginate').css({
    //         "position":"sticky",
    //         "bottom":"0",
    //         "left":"0",
    //     }); 
  
    // });
  });
    
    
  })(jQuery);
  