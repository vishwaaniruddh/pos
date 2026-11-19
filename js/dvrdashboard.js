function onload()
{
   // get_ai_ticket();
}
function setNetworkStatus(onlinepercent,offlinepercent){
	  var c3PieChart = c3.generate({
            bindto: '#c3-pie-chart1',
            data: {
              // iris data from R
              columns: [
                ['offline (%)', offlinepercent],
                ['online (%)', onlinepercent],
              ],
              type: 'pie',
              onclick: function(d, i) {
                console.log("onclick", d, i);
              },
              onmouseover: function(d, i) {
                console.log("onmouseover", d, i);
              },
              onmouseout: function(d, i) {
                console.log("onmouseout", d, i);
              }
            },
            color: {
              pattern: ['#FF5E6D', '#FF7300'] 
            },
            padding: {
              top: 0,
              right: 0,
              bottom: 30,
              left: 0,
            }
          });

          setTimeout(function() {
            c3PieChart.load({
              columns: [
                ['offline (%)', offlinepercent],
                ['online (%)', onlinepercent],
              ]
            });
          }, 1500);

        /*  setTimeout(function() {
            c3PieChart.unload({
              ids: 'offline'
            });
            c3PieChart.unload({
              ids: 'online'
            });
          }, 2500); */
}
function setPanelStatus(onlinepercent,offlinepercent){
	  var c3PieChart = c3.generate({
            bindto: '#c3-pie-chart',
            data: {
              // iris data from R
              columns: [
                ['Inactive', offlinepercent],
                ['Active', onlinepercent],
              ],
              type: 'pie',
              onclick: function(d, i) {
                console.log("onclick", d, i);
              },
              onmouseover: function(d, i) {
                console.log("onmouseover", d, i);
              },
              onmouseout: function(d, i) {
                console.log("onmouseout", d, i);
              }
            },
            color: {
              pattern: ['#EC6B56', '#FFC154']
            },
            padding: {
              top: 0,
              right: 0,
              bottom: 30,
              left: 0,
            }
          });
            /*
          setTimeout(function() {
            c3PieChart.load({
              columns: [
                ['Inactive (%)', offlinepercent],
                ['Active (%)', onlinepercent],
              ]
            });
          }, 1500); */

}
function setAlertStatus(alert_active_percent,alert_closed_percent){
	var c3PieChart = c3.generate({
            bindto: '#c3-pie-chart2',
            data: {
              // iris data from R
              columns: [
                ['Closed', alert_closed_percent],
                ['Active', alert_active_percent],
              ],
              type: 'pie',
              onclick: function(d, i) {
                console.log("onclick", d, i);
              },
              onmouseover: function(d, i) {
                console.log("onmouseover", d, i);
              },
              onmouseout: function(d, i) {
                console.log("onmouseout", d, i);
              }
            },
            color: { 
              pattern: ['#47B39C', '#EC6B56']
            },
            padding: {
              top: 0,
              right: 0,
              bottom: 30,
              left: 0,
            }
          });

          
}
function setAreaChart(label,data){
	var areaData = {
    labels: label,
    datasets: [{
      label: 'TicketCount',
      data: data,
      backgroundColor: [
        'rgba(255, 99, 132, 0.2)',
        'rgba(54, 162, 235, 0.2)',
        'rgba(255, 206, 86, 0.2)',
        'rgba(75, 192, 192, 0.2)',
        'rgba(153, 102, 255, 0.2)',
        'rgba(255, 159, 64, 0.2)'
      ],
      borderColor: [
        'rgba(255,99,132,1)',
        'rgba(54, 162, 235, 1)',
        'rgba(255, 206, 86, 1)',
        'rgba(75, 192, 192, 1)',
        'rgba(153, 102, 255, 1)',
        'rgba(255, 159, 64, 1)'
      ],
      borderWidth: 1,
      fill: true, // 3: no fill
    }]
  };

  var areaOptions = {
    plugins: {
      filler: {
        propagate: true
      }
    }
  }
	if ($("#areaChart").length) {
		var areaChartCanvas = $("#areaChart").get(0).getContext("2d");
		var areaChart = new Chart(areaChartCanvas, {
		  type: 'line',
		  data: areaData,
		  options: areaOptions
		});
	  }

}

function setAlertTypeChart(pielabel_array,pielabeldata_array){
	/*var c3PieChart = c3.generate({
            bindto: '#c3-pie-chart3',
            data: {
              // iris data from R
              columns: resdata,
              type: 'pie',
              onclick: function(d, i) {
                console.log("onclick", d, i);
              },
              onmouseover: function(d, i) {
                console.log("onmouseover", d, i);
              },
              onmouseout: function(d, i) {
                console.log("onmouseout", d, i);
              }
            },
            color: {
              pattern: ['#6153F9', '#FF5E6D', '#A7B3FD']
            },
            padding: {
              top: 0,
              right: 0,
              bottom: 30,
              left: 0,
            }
          }); */
		
      var doughnutPieData = {
		datasets: [{
		  data: pielabeldata_array,
		  backgroundColor: [
			'rgba(255, 99, 132, 0.5)',
			'rgba(54, 162, 235, 0.5)',
			'rgba(255, 206, 86, 0.5)',
			'rgba(75, 192, 192, 0.5)',
			'rgba(153, 102, 255, 0.5)',
			'rgba(255, 159, 64, 0.5)'
		  ],
		  borderColor: [
			'rgba(255,99,132,1)',
			'rgba(54, 162, 235, 1)',
			'rgba(255, 206, 86, 1)',
			'rgba(75, 192, 192, 1)',
			'rgba(153, 102, 255, 1)',
			'rgba(255, 159, 64, 1)'
		  ],
		}],

		// These labels appear in the legend and in the tooltips when hovering different arcs
		labels: pielabel_array
	  };
	  var doughnutPieOptions = {
		responsive: true,
		animation: {
		  animateScale: true,
		  animateRotate: true
		}
	  };		
	 if ($("#pieChart").length) {
		var pieChartCanvas = $("#pieChart").get(0).getContext("2d");
		var pieChart = new Chart(pieChartCanvas, {
		  type: 'pie',
		  data: doughnutPieData,
		  options: doughnutPieOptions
		});
	  }	  
}

$("#AtmID").change(function(){ debugger;
	getPanel_Detail();
	getPanelStatus_Detail();
	getSiteWise_Detail();
	getAlerts_Detail();
})
$("#Bank").change(function(){ debugger;
	getPanel_Detail();
	getPanelStatus_Detail();
	getSiteWise_Detail();
	getAlerts_Detail();
})
$("#Client").change(function(){ debugger;
	getPanel_Detail();
	getPanelStatus_Detail();
	getSiteWise_Detail();
	getAlerts_Detail();
})

        function getPanel_Detail(){
	        var Client = $("#Client").val();
			var Bank = $("#Bank").val();
			var AtmID = $("#AtmID").val();
			$("#dvr_online_count").html(0);
			$("#dvr_offline_count").html(0);
			$('#c3-pie-chart1').html('');
			//AtmID = "P1DCHY03";
			if(Client==''){
				swal("Oops!", "Bank Must Required !", "error");
				return false;
			}
			/*if(AtmID==''){
				swal("Oops!", "AtmID Must Required !", "error");
				return false;
			}*/
			
			$("#load").show();
			$.ajax({
				url: "dvrdashboard_ajax.php", 
				type: "POST",
				data: {client:Client,bank:Bank,atmid:AtmID},
				success: (function (result) { 
				   $("#load").hide();
				   debugger;
				
				   console.log(result);
				   var obj = JSON.parse(result);
				   var dvr_online_count = obj[0].dvr_online_count;
				//   $("#dvr_online_count").html(dvr_online_count);
				   var dvr_offline_count = obj[0].dvr_offline_count;
				 //  $("#dvr_offline_count").html(dvr_offline_count);
				   
				    if(dvr_online_count>0){
					   var dvrcounthtml = '<a target="_blank" href="dvrstatuslist.php?client='+Client+'&bank='+Bank+'&atmid='+AtmID+'&status=0">'+dvr_online_count+'</a>';
					   $("#dvr_online_count").html(dvrcounthtml);
				   }else{
				       $("#dvr_online_count").html(dvr_online_count);
				   }
				   var dvr_offline_count = obj[0].dvr_offline_count;
				   if(dvr_offline_count>0){
					   var offdvrcounthtml = '<a target="_blank" href="dvrstatuslist.php?client='+Client+'&bank='+Bank+'&atmid='+AtmID+'&status=1">'+dvr_offline_count+'</a>';
					   $("#dvr_offline_count").html(offdvrcounthtml);
				   }else{
				       $("#dvr_offline_count").html(dvr_offline_count);
				   }
				 
				   var onlinepercent = obj[0].total_online_percent;
				   var offlinepercent = obj[0].total_offline_percent;
				   setNetworkStatus(onlinepercent,offlinepercent);
				   
				})
		    });
		}  
		function getPanelStatus_Detail(){
	        var Client = $("#Client").val();
			var Bank = $("#Bank").val();
			var AtmID = $("#AtmID").val();
			$('#c3-pie-chart').html('');
			//AtmID = "P1DCHY03";
			if(Client==''){
				swal("Oops!", "Bank Must Required !", "error");
				return false;
			}
			/*if(AtmID==''){
				swal("Oops!", "AtmID Must Required !", "error");
				return false;
			}*/
			
			$("#load").show();
			$.ajax({
				url: "dvrdashboard_panelstatus_ajax.php", 
				type: "POST",
				data: {client:Client,bank:Bank,atmid:AtmID},
				success: (function (result) { 
				   $("#load").hide();
				   debugger;
				
				   console.log(result);
				   var obj = JSON.parse(result);
				/*   var dvr_online_count = obj[0].dvr_online_count;
				   $("#dvr_online_count").html(dvr_online_count);
				   var dvr_offline_count = obj[0].dvr_offline_count;
				   $("#dvr_offline_count").html(dvr_offline_count); */
				 
				   var onlinepercent = obj[0].total_online_percent;
				   var offlinepercent = obj[0].total_offline_percent;
				   setPanelStatus(onlinepercent,offlinepercent);
				   
				})
		    });
		}  
		
		function getAlerts_Detail(){
	        var Client = $("#Client").val();
			var Bank = $("#Bank").val();
			var AtmID = $("#AtmID").val();
			$("#total_alerts_count").html(0);
			$('#c3-pie-chart').html('');
			//AtmID = "P1DCHY03";
			if(Client==''){
				swal("Oops!", "Bank Must Required !", "error");
				return false;
			}
			/*if(AtmID==''){
				swal("Oops!", "AtmID Must Required !", "error");
				return false;
			}*/
			
			$("#load").show();
			$.ajax({
				url: "dvrdashboard_alerts_ajax.php", 
				type: "POST",
				data: {client:Client,bank:Bank,atmid:AtmID},
				success: (function (result) { 
				   $("#load").hide();
				   debugger;
				
				   console.log(result);
				   var obj = JSON.parse(result);
				/*   var dvr_online_count = obj[0].dvr_online_count;
				   $("#dvr_online_count").html(dvr_online_count);
				   var dvr_offline_count = obj[0].dvr_offline_count;
				   $("#dvr_offline_count").html(dvr_offline_count); */
				   var resdata = obj[0].res_data;
				   var pielabel_array = obj[0].pielabel_array;
				   var pielabeldata_array = obj[0].pielabeldata_array;
				   var label = obj[0].label;
				   var label_data = obj[0].label_data;
				   var alert_active_percent = obj[0].alert_active_percent;
				   var alert_closed_percent = obj[0].alert_closed_percent;
				   var totalalerts = obj[0].totalalerts;
				   var alert_resolved_count = obj[0].alert_resolved_count;
				   var alert_unresolved_count = obj[0].alert_unresolved_count;
				   $("#total_alerts_count").html(totalalerts);
				   $("#total_active").html(alert_unresolved_count);
				   $("#total_closed").html(alert_resolved_count);
				   setAlertStatus(alert_active_percent,alert_closed_percent);
				   setAreaChart(label,label_data);
				   setAlertTypeChart(pielabel_array,pielabeldata_array);
				})
		    });
		}  
		
		function getSiteWise_Detail(){
	        var Client = $("#Client").val();
			var Bank = $("#Bank").val();
			var AtmID = $("#AtmID").val();
			
			$("#siteonline_percent_table").html('');
			if(Client==''){
				swal("Oops!", "Client Must Required !", "error");
				return false;
			}
			
			/*if(AtmID==''){
				swal("Oops!", "AtmID Must Required !", "error");
				return false;
			}*/
			$("#load").show();
			$.ajax({
				url: "dashboard_branchwise_table_ajax.php", 
				type: "GET",
				data: {client:Client,bank:Bank,atmid:AtmID},
				dataType: "html", 
				success: (function (result) { 
				   $("#load").hide();
				   
				   $('#order-listing').dataTable().fnClearTable();
				   $('#siteonline_percent_table').html('');
				   $("#siteonline_percent_table").html(result);
				   $('#order-listing').DataTable(
						{
							"order": [[ 0, "desc" ]]
						}
					);
				})
		    });
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
			})
		})
	}
function onchangebank() { 
		var client = $("#Client").val();
		$("#Bank").html('');
		$("#AtmID").html('<option value="">Select</option>');
		$.ajax({
			type: "GET",
			url: "getMasterData.php", 
			data: {client:client},
			dataType: "html",
			success: (function (result) {
				
				$("#Bank").html(result);
			})
		})
	}	


