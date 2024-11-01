	
		function Config() {
	"use strict";
}
Config.endpoint  = vars.endpoint;
Config.authUser  = vars.user;
Config.authPassword = vars.pass;
Config.registration = "<registration-uuid>";


var myLRS = new TinCan.LRS({
	endpoint: Config.endpoint, 
	version: "1.0",
	auth: 'Basic ' + Base64.encode(Config.authUser + ':' + Config.authPassword)
});



var myActor = new TinCan.Agent({
	name : vars.actorname,
	mbox : "mailto:"+vars.email
});

var myActivityDefinition = new TinCan.ActivityDefinition({
		name : {
			"en-US": vars.coursename,
			"en-GB": vars.coursename
		},
		description : {
			"en-US": vars.description,
			"en-GB": vars.description
		}
	});
 
	var myActivity = new TinCan.Activity({
		id : window.location.href,
		definition : myActivityDefinition
	});
	
	var myVerb = new TinCan.Verb({
		id : "http://adlnet.gov/expapi/activities/"+vars.adltype,
		display : {
			"en-US": vars.display, 
			"en-GB": vars.display
		}
	});
			
			var act = {
            id: window.location.href,
            definition: {
                name: {
                    "en-US": vars.coursename
                },
                description: {
                    "en-US": vars.description
                },
                type: "http://adlnet.gov/expapi/activities/"+vars.adltype
            }
        };
                tincan = new TinCan();
				tincan.recordStores[0] = myLRS;
				tincan.actor = myActor;
				
				

	var stmt = new TinCan.Statement({
		actor : myActor,
		verb : myVerb,
		target : myActivity
	},false);

        tincan.sendStatement(stmt);
		if( vars.verb == "commented" ){
		document.cookie = "comment=empty";
		}
		if( vars.verb == "experienced" ){
		document.cookie = "experienced=empty";
		}
		if( vars.display == "experienced topic" ){
		document.cookie =  vars.description+'=experienced; path=/';
		}
		if( vars.display == "launched quiz" ){
		document.cookie =  vars.coursename+'=launched; path=/';
		document.cookie = "forcert=empty";
		}
		if( vars.display == "failed quiz" ){
		document.cookie =  'lastquiz=empty; path=/';
		window.location = vars.redir;
		}
		if( vars.display == "failed course" ){
		document.cookie =  'lastquiz=empty; path=/';
		window.location = vars.redir;
		}
		if( vars.display == "passed quiz" ){
		document.cookie =  'lastquiz=empty; path=/';
		document.cookie = "forcert="+vars.description+"; path=/";
		}
		if( vars.display == "passed course" ){
		document.cookie =  'lastquiz=empty; path=/';
		document.cookie = "forcert="+vars.description+"; path=/";
		}