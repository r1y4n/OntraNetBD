$(document).ready(function() {
	$("#search").click(function() {
		var key = $("#key").val();
		var dataString = 'key='+ key;
		if(key=='') {
			alert("Please Fill All Fields");
		}
		else {
			$.ajax({
				type: "POST",
				url: "queryProcess.php",
				data: dataString,
				cache: false,
				success: function(result){
					result = '<h4 style="color: #fff;">Search result for "' + key + '":</h4>' + result;
					$("#result").html(result);					
				}
			});
		}
		return false;
	});
});
