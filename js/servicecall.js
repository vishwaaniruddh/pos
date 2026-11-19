function onload()
{
   // get_servicecall();
}


$('#AtmID').change(function()
{
    get_servicecall();
});

function get_servicecall()
{
    debugger;
   var AtmID= $("#AtmID").val(); 
   var start= $("#start").val(); 
   var end= $("#end").val(); 
  // AtmID = "B1088910";
   
   if(AtmID=='')
    {
    	//swal( "AtmID Must Required !");
    	return false;
    }
   
    $.ajax({
        url: "servicecall_ajax.php", 
        type: "GET",
        data: {atmid:AtmID,start:start,end:end},
        dataType: "html", 
        success: (function (result) { debugger;
           console.log(result);
         
            $('#order-listing').dataTable().fnClearTable();
            $('#servicecall_tbody').html('');
            $('#servicecall_tbody').html(result);
            $('#order-listing').DataTable();
           
        })
    });
}

function onchangeatmid() {
		var bank = $("#Bank").val();
		$.ajax({
			type: "GET",
			url: "getMasterData.php", 
			data: {bank:bank},
			dataType: "html",
			success: (function (result) {
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