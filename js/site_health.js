$("#AtmID").change(function(){
	count_online_offline();
});


function test(){
	 var Client= $("#Client").val(); 
	 var Bank= $("#Bank").val(); 
	 var AtmID= $("#AtmID").val(); 
    // AtmID = "P1DCHY03";
	  $.ajax({
				url: "api/dvrdashboard_alerts_ajax.php", 
				type: "POST",
				data: {atmid:AtmID,client:Client,bank:Bank,user_id:24},
				success: (function (result) { debugger;
				   var res = JSON.parse(result);
					console.log(res);
				})
			});
}

function onload()
{
    // get_dvr_health_online();
    // get_dvr_health_offline();
	// count_online_offline();
}
function count_online_offline(){
	 var Client= $("#Client").val(); 
	 var Bank= $("#Bank").val(); 
	 var AtmID= $("#AtmID").val(); 
	 $('#dvr_online').html('0');
	 $('#dvr_offline').html('0');
	 $('#panel_online').html('0');
	 $('#panel_offline').html('0');
    // AtmID = "P1DCHY03";
	  $.ajax({
				url: "diff_site_health_online_offline_count.php", 
				type: "POST",
				data: {atmid:AtmID,client:Client,bank:Bank},
				success: (function (result) { debugger;
				   console.log(result);
					var data = result.split("_");
					$('#dvr_online').html('');
					$('#dvr_online').html(data[0]);
					$('#dvr_offline').html('');
					$('#dvr_offline').html(data[1]);
					
					get_dvr_health_online();
				})
			});
}

function test(){
	var settings = {
	  "url": "https://timer.lightingmanager.in/panelmeterlatestlog?org_id=147&mac_id=18001813",
	  "method": "GET",
	  "timeout": 0,
	  "headers": {
		"access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6IjYwYmRkNDc2OWMwNzJlNzJmNzhmOGNjMiIsImVtYWlsIjoiYXNoaXNoQGNzc2luZGlhLmluIiwib3JnX2lkIjoxNDcsImdyb3VwX2lkcyI6WyIwIiwiMSIsIjIiXSwicmVhZCI6ODE4NSwid3JpdGUiOjgxODUsInJvbGVfaWQiOjExLCJpYXQiOjE2NDEyMjYxOTgsImV4cCI6MTY0MTMxMjU5OH0.sY-EHPqIQpAZALc46EICQTRVvnDJi61gHaxkiO9crNU"
	  },
	};

	$.ajax(settings).done(function (response) {
	  console.log(response);
	});
}

function get_dvr_health_online()
{
	
	test();
	
	var Client= $("#Client").val(); 
	var Bank= $("#Bank").val(); 
   var AtmID= $("#AtmID").val(); 
   $('#sitehealth_tbody').html('');
   $("#load").show();
 //  AtmID = "P1DCHY03";
   if(Client=='')
    {
    	//swal("Oops!", "AtmID Must Required !", "error");
    	return false;
    }
    $.ajax({
			url: "diff_site_views_dvrhealth_online.php", 
			type: "GET",
			data: {atmid:AtmID,client:Client,bank:Bank},
			dataType: "html", 
			success: (function (result) { debugger;
			   console.log(result);
			   $("#load").hide();
				$('#order-listing').dataTable().fnClearTable();
				$('#sitehealth_tbody').html('');
				$('#sitehealth_tbody').html(result);
				$('#order-listing').DataTable();
				
				
				get_dvr_health_offline();
			})
		});
}   

function get_dvr_health_offline()
{
	var Client= $("#Client").val(); 
	 var Bank= $("#Bank").val(); 
   var AtmID= $("#AtmID").val(); 
   $("#load").show();
  // AtmID = "P3ENCP01";
   if(Client=='')
    {
    	swal("Oops!", "AtmID Must Required !", "error");
    	return false;
    }
    $.ajax({
        				url: "diff_site_views_dvrhealth_offline.php", 
        				type: "GET",
        				data: {atmid:AtmID,client:Client,bank:Bank},
						dataType: "html", 
        				success: (function (result) { debugger;
        				   console.log(result);
                           $('#order-listing2').dataTable().fnClearTable();
                            $('#sitehealth_tbody_offline').html('');
                            $('#sitehealth_tbody_offline').html(result);
                            $('#order-listing2').DataTable();
                           $("#load").hide();
                           
                        })
                    });
} 

function onchangeatmid() {
				var bank = $("#Bank").val();
				$("#AtmID").html('<option value="">Select</option>');
				$.ajax({
					type: "GET",
					url: "getMasterData.php", 
					data: {dvrbank:bank},
					dataType: "html",
					success: (function (result) {
						$("#AtmID").html(result);
						count_online_offline();
					})
				})
			}
		function onchangebank() { 
				var client = $("#Client").val();
				$("#AtmID").html('<option value="">Select</option>');
				$.ajax({
					type: "GET",
					url: "getMasterData.php", 
					data: {dvrclient:client},
					dataType: "html",
					success: (function (result) {
						$("#Bank").html('');
						$("#Bank").html(result);
						count_online_offline();
					})
				})
			}	
