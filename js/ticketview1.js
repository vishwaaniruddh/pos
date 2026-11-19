function getTicketDetails()
{
  get_ticketview();
}
$("#portal").change(function(){ debugger;
	get_ticketview();
});
$("#AtmID").change(function(){

   // get_ticketview();
});

function get_ticketview()
{
    var Client= $("#Client").val();
    var Bank= $("#Bank").val();   	
    var Atmid= $("#AtmID").val(); 
    var start= $("#start").val(); 
    var end= $("#end").val(); 
    var portal = $("#portal").val();
   // $('#ticketview_tbody').html('');
    if(Client=='' )
    {
    	//swal("Oops!", "AtmID Must Required !", "error");
    	return false;
    }
	
	$("#excel_client").val(Client);
	$("#excel_bank").val(Bank);
	$("#excel_atmid").val(Atmid);
	$("#start_date").val(start);
	$("#start_end").val(end);
	$("#excel_portal").val(portal);
	
	$('#example').DataTable({
		
		 "bProcessing": true,
         "serverSide": true,
		 "lengthMenu": [
		   [10, 25, 50, -1],
		   [10, 25, 50, "All"]
		],
		 "order": [[ 0, "desc" ]],
					"dom": 'Bfrtip',
					 "buttons": [
                        {
                            "extend": 'excel',
                            "text": '<button class="btn"><i class="fa fa-file-excel-o" style="color: green;"></i>  Excel</button>',
                            "titleAttr": 'Excel',
                            "action": newexportaction
                        },
                    ],
         "ajax":{
            url :"ticketview_ajax2.php", // json datasource
            type: "post",  // type of method  ,GET/POST/DELETE
			data: {client:Client,bank:Bank,atmid:Atmid,start_date:start,end_date:end,portal:portal},
            error: function(){
             // $("#employee_grid_processing").css("display","none");
            },
		 columns: [
					{ data: "atmid" },
					{ data: "alert_type" },
					{ data: "panelid" },
					{ data: "location" },
					{ data: "address" },
					{ data: "state" },
					{ data: "city" },
					{ data: "branch_code" },
					{ data: "alarm" },
					{ data: "zone" },
					{ data: "createdatetime" },
					{ data: "closedatetime" },
					{ data: "duration" },
					{ data: "dvrip" },
					{ data: "comment" },
					{ data: "id" },
					{ data: "closedby" }
				  ]	
          }
        });   
	
	//$("#load").show();
/*	
	var myTable = $('#example').DataTable({
    "serverSide": true,
	"bProcessing": true,
   // "processing": true,
    "paging": true,
    "searching": { "regex": true },
    "lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
    "pageLength": 10,
    "ajax": {
        "type": "POST",
        "url": "ticketview_ajax1.php",
        "dataType": "json",
        "contentType": 'application/json; charset=utf-8',
        "data": function (data) { debugger;
            // Grab form values containing user options
            var form = {};
            $.each($("form").serializeArray(), function (i, field) {
                form[field.name] = field.value || "";
            });
            // Add options used by Datatables
            var info = { "start": 0, "length": 10, "draw": 1 };
            $.extend(form, info);
            return JSON.stringify(form);
        },
        "complete": function(response) {
            console.log(response);
       }
    }
});
	*/
	
	/*
	$('#example').DataTable( {
		"processing": true,
        "serverSide": true,
		"paging": true,
		"searching": { "regex": true },
        "lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
		"pageLength": 10,
		 "ajax": {
			url: 'ticketview_ajax1.php',
			type: 'POST',
			data: {client:Client,bank:Bank,atmid:Atmid,start:start,end:end,portal:portal},
            dataType: "json" 
		},
		 columns: [
					{ data: "atmid" },
					{ data: "panelid" },
					{ data: "location" },
					{ data: "address" },
					{ data: "state" },
					{ data: "city" },
					{ data: "branch_code" },
					{ data: "alert_type" },
					{ data: "alarm" },
					{ data: "zone" },
					{ data: "createdatetime" },
					{ data: "closedatetime" },
					{ data: "duration" },
					{ data: "dvrip" },
					{ data: "comment" },
					{ data: "id" },
					{ data: "closedby" }
				  ]		 
	} );  */
	/*
	 $('#example').DataTable( {
        serverSide: true,
        ordering: false,
        searching: false,
        ajax: function ( data, callback, settings ) {
            var out = [];
 
            for ( var i=data.start, ien=data.start+data.length ; i<ien ; i++ ) {
                out.push( [ i+'-1', i+'-2', i+'-3', i+'-4', i+'-5' ] );
            }
 
            setTimeout( function () {
                callback( {
                    draw: data.draw,
                    data: out,
                    recordsTotal: 5000000,
                    recordsFiltered: 5000000
                } );
            }, 50 );
        },
        scrollY: 200,
        scroller: {
            loadingIndicator: true
        },
    } );  */
	/*
	if ($(".table-responsive").hasClass("hidden")) {
      $(".table-responsive").removeClass("hidden");
      $.ajax({
        url: "ticketview_ajax1.php",
		data: {client:Client,bank:Bank,atmid:Atmid,start:start,end:end,portal:portal},
        type: "GET"
      }).done(function (result) { 
	    console.log(result); 
      //  animal_table.clear().draw();
      //  animal_table.rows.add(result).draw();
		    $("#example").DataTable({
				  pageLength: 20,
				  lengthMenu: [20, 30, 50, 75, 100],
				  order: [],
				  paging: true,
				  searching: true,
				  info: true,
				  data: result,
				  columns: [
					{ data: "atmid" },
					{ data: "panelid" },
					{ data: "location" },
					{ data: "address" },
					{ data: "state" },
					{ data: "city" },
					{ data: "branch_code" },
					{ data: "alert_type" },
					{ data: "alarm" },
					{ data: "zone" },
					{ data: "createdatetime" },
					{ data: "closedatetime" },
					{ data: "duration" },
					{ data: "dvrip" },
					{ data: "comment" },
					{ data: "id" },
					{ data: "closedby" }
				  ]
				});
      });
    }
	*/
	/*
    $.ajax({
        url: "ticketview_ajax1.php", 
        type: "GET",
        data: {client:Client,bank:Bank,atmid:Atmid,start:start,end:end,portal:portal},
        dataType: "json", 
        success: (function (result) { debugger;
           console.log(result);
         
           $('#order-listing').dataTable().fnClearTable();
            
            //$('#order-listing').DataTable().ajax.reload(); 
                
            //    $('#order-listing').dataTable().fnDestroy();
           var example =  $('#order-listing').DataTable(
			    {
					"order": [[ 0, "desc" ]],
					dom: 'Bfrtip',
					  buttons: [
						  'excelHtml5'
					  ],
					"searching": false, //this is disabled because I have a custom search.
					"aaData": [result], //here we get the array data from the ajax call.
					
				}
			);
			example.ajax.reload(); 
			$("#load").hide();
        })
    }); */
}

function onchangeatmid() {
		var bank = $("#Bank").val();
		$("#AtmID").html('<option value="">Select</option>');
		$.ajax({
			type: "GET",
			url: "getMasterData.php", 
			data: {bank:bank},
			dataType: "html",
			success: (function (result) {
				
				$("#AtmID").html(result);
				//get_ticketview();
			})
		})
	}
function onchangebank() { 
		var client = $("#Client").val();
		$("#AtmID").html('<option value="">Select</option>');
		$("#Bank").html('');
		$.ajax({
			type: "GET",
			url: "getMasterData.php", 
			data: {client:client},
			dataType: "html",
			success: (function (result) {
				$("#Bank").html(result);
				
				//get_ticketview();
			})
		})
	}	
