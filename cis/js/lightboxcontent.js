var course_ids = [59];
var selector = "body.course-{{course_id}} div[role=main] img"
var selectors = [];
$.each(course_ids, function(index, value){
   selectors.push(selector.replace("{{course_id}}",value)); 
});

$(document).ready(function(){
   $(selectors.join(",")).click(function(){
       $(this).lightbox_me(); 
   }).hover(function(){
       $(this).css("cursor", "pointer"); 
   });
});
