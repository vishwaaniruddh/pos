function onload()
{
  //  get_ticketviewservice();
}

$('#AtmID').change(function()
{
    get_ticketviewservice();
});

function get_ticketviewservice()
{
    debugger;
  
    var Atmid= $("#AtmID").val(); 
    var start= $("#start").val(); 
    var end= $("#end").val(); 
     if(Atmid=='')
     {
       //  swal(" PanelID Must Required ");
         return false;
     }

        $.ajax({
                        url: "ticketviewservice_ajax.php", 
        				type: "GET",
        				data: {atmid:Atmid,start:start,end:end},
						dataType: "html", 
        				success: (function (result) { debugger;
        				   console.log(result);
                         
                            $('#order-listing').dataTable().fnClearTable();
							$('#ticketviewservice_tbody').html('');
							$('#ticketviewservice_tbody').html(result);
                            $('#order-listing').DataTable({
                                "order": [[ 0, "desc" ]]
                            } );
                           
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