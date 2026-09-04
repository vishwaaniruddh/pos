function onload()
{
  //  get_ticketview();
}

$("#AtmID").change(function(){

 //   getSnapsDetails();
});

function getSnapsDetails()
{
	debugger;
    var Atmid= $("#AtmID").val(); 
    if(Atmid !== null && Atmid !== '') {
	   $.ajax({
			url: "snaps_ajax.php", 
			type: "GET",
			data: {atmid:Atmid},
			dataType: "html", 
			success: (function (result) { debugger;
			   console.log(result);
			 
				$('#snaps_details').html('');
				$('#snaps_details').html(result); 
				
				
			})
		});
	}
    else
    {
    	//swal("Oops!", "AtmID Must Required !", "error");
    	return false;
    }
    
}

function onchangeatmid() {
		var bank = $("#Bank").val();
		var client = $("#Client").val();
		$.ajax({
			type: "GET",
			url: "getMasterData.php", 
			data: {bankname:bank,clientname:client},
			dataType: "html",
			success: (function (result) { debugger;
				$("#AtmID").html('');
				$("#AtmID").html(result);
			})
		})
	}
function onchangebank() { 
		var client = $("#Client").val();
		$.ajax({
			type: "GET",
			url: "getMasterData.php", 
			data: {client:client},
			dataType: "html",
			success: (function (result) {
				$("#Bank").html('');
				$("#Bank").html(result);
			})
		})
	}	
