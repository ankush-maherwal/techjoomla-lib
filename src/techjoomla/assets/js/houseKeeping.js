if (typeof(techjoomla) == 'undefined')
{
	var techjoomla = {};
}

if (typeof techjoomla.jQuery == "undefined")
{
	techjoomla.jQuery = jQuery;
}

var tjHouseKeepingScriptsCount = 0;
var tjEachScriptProgress = 0;
var tjTotalScripts = 0;

var TjHouseKeeping = {

	fixDatabase: function (client, controller){

		var initResponse = '';

		/* Add required HTML elements in the body*/
		jQuery('<div class="fix-database-info"><div class="progress-container"></div></div>').insertBefore(".tjBs3");

		/* Disable the fix database button*/
		jQuery('#toolbar-refresh button').prop('disabled', true);

		/* Initialise the houseKeeping */
		jQuery.ajax({
			url: 'index.php?option='+client+'&task='+controller+'.init'+'&tmpl=component',
			type: 'POST',
			dataType:'json',
			success: function(response)
			{
				initResponse = response;
				tjEachScriptProgress = parseFloat(100/response.count,10);
				tjTotalScripts = response.count;
			},
			error: function(jqXHR, textStatus, errorThrown)
			{
				Joomla.renderMessages({'error':["Something went wrong"]});
			}
		}).done(function(){
			tjHouseKeepingScriptsCount = 0;
			jQuery('.tjBs3').hide();
			jQuery('.fix-database-info').html('<div class="progress-container"></div>');
			jQuery('.fix-database-info').show();

			if (initResponse.scripts.length > 0)
			{
				/* Initialise progress bar */
				var obj = jQuery('.fix-database-info .progress-container');
				var progressBarObj = new TjHouseKeeping.createProgressbar(obj);
				let tjHouseKeepingCounter = 0;

				initResponse.scripts.forEach(function(script)
				{
					statusdiv = "<div class='alert alert-plain tjHouseKeepingScriptDiv"+tjHouseKeepingCounter+"'>" +
									"<div class='before'>Fixing database:&nbsp;"+script[3]+"</div>"+
									"<div class='after'>"+script[4]+"</div>" +
								"</div>";
					jQuery('.fix-database-info').append(statusdiv);

					tjHouseKeepingCounter++;
				});
	
				TjHouseKeeping.extecuteHouseKeeping(initResponse.scripts, client, controller, progressBarObj);
			}
			else
			{
				statusdiv = "<div class='alert alert-info'>" +
								"<div>Database upto date.</div>"+
							"</div>";
				jQuery('.fix-database-info').append(statusdiv);
			}
		})

		return false;
	},

	extecuteHouseKeeping:function (scripts, client, controller, progressBarObj){
		let script = scripts[tjHouseKeepingScriptsCount];

		jQuery.ajax({
			url: 'index.php?option='+client+'&task='+controller+'.executeHouseKeeping&client='+script[0]+'&version='+script[1]+'&script='+script[2]+'&tmpl=component',
			type: 'POST',
			dataType:'json',
			success: function(response)
			{
				var progressPercent = parseInt(tjEachScriptProgress * (tjHouseKeepingScriptsCount+1));
				progressBarObj.setProgress(progressPercent);

				if (response === true)
				{
					jQuery('.fix-database-info .tjHouseKeepingScriptDiv'+tjHouseKeepingScriptsCount).removeClass('alert-plain').addClass('alert-success');
					jQuery('.fix-database-info .tjHouseKeepingScriptDiv'+tjHouseKeepingScriptsCount+' .after').append('...Done!');
				}
				else
				{
					jQuery('.fix-database-info .tjHouseKeepingScriptDiv'+tjHouseKeepingScriptsCount).removeClass('alert-plain').addClass('alert-error');
					jQuery('.fix-database-info .tjHouseKeepingScriptDiv'+tjHouseKeepingScriptsCount+' .after').append('...Something went wrong!');
				}

				tjHouseKeepingScriptsCount++;

				if (tjHouseKeepingScriptsCount == tjTotalScripts)
				{
					jQuery('#toolbar-refresh button').prop('disabled', false);
				}
				else
				{
					TjHouseKeeping.extecuteHouseKeeping(scripts, client, controller, progressBarObj);
				}
			},
			error: function(jqXHR, textStatus, errorThrown)
			{
				jQuery('.fix-database-info').removeClass('alert-plain').addClass('alert-error');
				jQuery('.fix-database-info .after').html(jqXHR.responseText);
				jQuery('.fix-database-info .after').show();
			}
		});
	},

	createProgressbar: function(obj, bartitle){
		bartitle = bartitle ? bartitle : 'Fixing database:&nbsp;';
		this.statusbar = jQuery("<div></div>");
		this.progressBar = jQuery('<div class="progress progress-striped active progress-bar"><span class="bar progress-bar">' + bartitle + ' <b class="progress_per"></b></div>').appendTo(this.statusbar);

		obj.append(this.statusbar);

		this.setProgress = function(progress)
		{
			this.statusbar.show();
			this.progressBar.show();
			var progressBarWidth =progress*this.progressBar.width()/ 100;
			this.progressBar.find('.progress-bar').animate({ width: progressBarWidth }, 10);
			this.progressBar.find('.progress_per').html(progress + "% ");
		}
	}
}
