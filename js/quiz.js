$jq =jQuery.noConflict();

$jq(document).ready(function($jq) {

    $jq("#post-body-content").prepend('<div id="quiz_error" class="error" style="display:none" ></div>');

    $jq('#post').submit(function() {

        if ( $jq("#post_type").val() =='quiz' ) {

            return wpq_validate_quizes();
        }

    });

});

var duration = quiz.quizDuration * 60;
$jq(document).ready(function(){

    $jq('#slider').rhinoslider({
        controlsMousewheel: false,
        controlsPlayPause: false,
        showBullets: 'never',
        showControls: 'always',
		controlsKeyboard: false,
		nextText: 'Next Question',
		prevText: 'Previous Question',
		cycled: true
    });
    
    setTimeout("startPuzzleCount()",1000);     

    $jq("#completeQuiz").click(function(){
        wpq_quiz_results();
    });

});

var wpq_quiz_results = function(){
    var selected_answers = {};
    $jq(".ques_answers").each(function(){
        var question_id = $jq(this).attr("data-quiz-id");

        var selected_answer = $jq(this).find('input[type=radio]:checked');
        if(selected_answer.length != 0){
            var selected_answer = $jq(selected_answer).val();
         
            selected_answers["qid_"+question_id] = selected_answer;
            
        }else{
            selected_answers["qid_"+question_id] = '';
        }

    });


    $jq.post(quiz.ajaxURL, {
        action:"get_quiz_results",
        nonce:quiz.quizNonce,
        data : selected_answers
    }, function(data) {
        var total_questions = data.total_questions;
		var total_correct = 0;
        $jq('#slider').data('rhinoslider').next($jq('#rhino-item'+total_questions));
        $jq('#score').html( data.score +"/"+total_questions);

        var result_html = "<table>";
        result_html += "<tr><td>Question</td><td>Result</td></tr>";
        var quiz_index = 1;
        $jq.each(data.result, function( key, ques ) {
            result_html += "<tr><td>"+quiz_index+"</td>";
			if(ques.mark == "correct"){ total_correct += 1; }
            result_html += "<td><img src='"+quiz.plugin_url+"img/"+ques.mark+".png' /></td></tr>";

            quiz_index++;
        });

        result_html += "</table>";
		var total_result = 100/total_questions;
		var score = total_correct * total_result;
		var pass = 0;
		if( score >= 70 ){
		pass = 1;
		}
		result_html += "<h2>You Scored a " + score + "% Score";
		if( pass == 1){
		result_html += ". You Passed! Great Job!</h2>";
		document.cookie =  'lastquiz=passed; path=/';
		}else{ result_html += ", However You Did Not Pass</h2>"; 
		document.cookie =  'lastquiz=failed; path=/';
		}

        $jq("#quiz_result").html(result_html);
         $jq('#timer').hide();
		 $jq('.rhino-next').hide(); 
		 $jq('.rhino-prev').hide(); 
		 $jq('#completeQuiz').hide(); 
		 $jq('#done').show(); 

    }, "json");  
};

var wpq_validate_quizes = function(){
    var err = 0;

    $jq("#quiz_error").html("");
    $jq("#quiz_error").hide();

    if($jq("#title").val() == ''){
        $jq("#quiz_error").append("<p>Please enter Question Title.</p>");
        err++;
    }

    var correct_answer = $jq("#correct_answer").val();
    if($jq("#quiz_answer"+correct_answer).val() == ""){
        $jq("#quiz_error").append("<p>Correct answer cannot be empty.</p>");
        err++;
    }

    if(err>0){
        $jq("#publish").removeClass("button-primary-disabled");
        $jq(".spinner").hide();
        $jq("#quiz_error").show();

        return false;
    }else{
        return true;
    }
};

function secondsToTime(secs)
{
    var hours = Math.floor(secs / (60 * 60));
   
    var divisor_for_minutes = secs % (60 * 60);
    var minutes = Math.floor(divisor_for_minutes / 60);
 
    var divisor_for_seconds = divisor_for_minutes % 60;
    var seconds = Math.ceil(divisor_for_seconds);
   
   if(hours != 0){
     return hours + " Hours: " + minutes + " Minutes " + seconds + " Seconds";
	 }else{
	 return minutes + " Minutes: " + seconds + " Seconds";
	 }
}


var startPuzzleCount = function(){
if(quiz.quizDuration != "0"){
    duration--;
	var display = secondsToTime(duration);
    $jq('#timer').html(display+" Remaining");
    if(duration == '0'){
        $jq('#timer').html("Time Up");
        wpq_quiz_results();
        return;
    }
    setTimeout("startPuzzleCount()",1000);
	}else{
	    $jq('#timer').html("There is No Time Limit");
	}
};