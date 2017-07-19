(function($){
    function authenticateTrello(){
        var authenticationSuccess = function() { console.log('Successful authentication'); };
        var authenticationFailure = function() { console.log('Failed authentication'); };
        Trello.authorize({
          type: 'popup',
          name: 'Getting Started Application',
          scope: {
            read: 'true',
            write: 'true' },
          expiration: 'never',
          success: authenticationSuccess,
          error: authenticationFailure
        });
    }
    function submitProblem(user, feedback){
        authenticateTrello();
        var myList = "596d1cc89de495732a9cf1ae";
        var creationSuccess = function(data) {
          console.log('Card created successfully. Data returned:' + JSON.stringify(data));
        };
        var now = new Date();
        var month = now.getMonth() + 1;
        var date = now.getDate();
        var year = now.getFullYear();
        var newCard = {
          name: user+" reported an error on "+month+"/"+date+"/"+year, 
          desc: feedback,
          // Place this card at the top of our list 
          idList: myList,
          pos: 'top',
          labels:"55bfb4b019ad3a5dc2fde0a9",
          urlSource:window.location.href
        };
        Trello.post('/cards/', newCard, creationSuccess);
    }
    
//==============================================================================
//==================================== Trello ==================================
//==============================================================================

    $("#trello-continue").click(function(){
        var page = $("#modalTrelloBody").data("page");
        var user = $("#modalTrelloBody").data("user");
        var feedback = $("#tfeedback").val();
        submitProblem(user, feedback);
        $("#tfeedback").val('');
    });
    $("#leave_feedback").click(function(){
		  $("#modal-trello").modal("show");
		  $("#tfeedback").focus();
    });
})(jQuery);