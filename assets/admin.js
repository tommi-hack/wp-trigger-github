;(function ($, window, document, undefined) {
    $(function () {
        
      var image = $('#github_actions_dashboard_status img');
      var imageSrc = image.prop('src');
      var refreshTimout = null;
      
      var updateBadgeUrl = function () {
        if (!image.length) return;
        var d = new Date();
        var suffix = imageSrc.includes('?') ? '&' : '?';
        image.prop('src', imageSrc + suffix + 'v=s_' + d.getTime());
        refreshTimout = setTimeout(updateBadgeUrl, 15000);
      };

      refreshTimout = setTimeout(updateBadgeUrl, 15000);
      
      $('.wp-trigger-github-deploy-button').click(function (e) {
        e.preventDefault();
        var dialog = confirm('Start deployment to live server?');
        if (!dialog) return;
        $.ajax({
          type: 'POST',
          url: wpjd.url,
          data: JSON.stringify({event_type: 'dispatch'}),
          dataType: 'json',
          contentType: 'application/json',
          beforeSend: function (xhr) {
            xhr.setRequestHeader('Authorization', 'token ' + wpjd.token);
            xhr.setRequestHeader('Accept', 'application/vnd.github.everest-preview+json');
            xhr.setRequestHeader('Content-Type', 'application/json');
          },
          success: function (e) {
            alert('Deployment successfully started! This can take up to 5 minutes.');
            updateBadgeUrl();
          },
          error: function(jqXHR, textStatus, errorThrown) {
            alert('An ERROR occured during deployment. Please get in touch with Riccardo.');
            console.error(jqXHR, textStatus, errorThrown);
          }
        });
        clearTimeout(refreshTimout);
      });
    });
})(jQuery, window, document);
