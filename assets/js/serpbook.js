jQuery(document).ready(function(){

  const $ = jQuery;

  let el = $("#sbapi__results");
  let lastUpdatedEl = $("#sbapi__lastupdated");
  /**
   * Get value of hidden input to load initial data
   */
   let url = $("#sbapi_hidden_viewkey_url").val();
   showResults(el, lastUpdatedEl, url)



  $("#sbapi_select_view_key").change(function(e) {

    let url = e.target.value;
    // Clears HTML on change
    el.html('');

    // Add a loading state while data is be fetched
    el.html("<div class='sbapi_loading'>Loading....</div>")

    // Display the data
    showResults(el, lastUpdatedEl, url)

  });

  /**
   * Fetches data from serpbook
   *
   * @param el  div element
   * @param lastUpdatedEl div element
   * @param url String
   */

  function showResults(el, lastUpdatedEl, url) {
    $.get({
      url: url,
      processData: false
    }).then(res => {

      // Remove loading state and replace with data
      el.html('');

      let allGranks = _.pluck(res, 'grank')
      let todaysArray = _.reject(allGranks, (g) => g == 0)
      // Comparing today's ranking
      let top3 = _.countBy(todaysArray, (t) => t <= 3)
      let top10 = _.countBy(todaysArray, (t) => t <= 10)
      let top20 = _.countBy(todaysArray, (t) => t <= 20)


      var lastUpdateObj;
      res.map((r, i) => {

        // This will hold just one object as we only need it once.
        lastUpdateObj = r.lastupdate;

        el.append(`
            <div id="row-${i}" class='sbapi__results__row'>
              <div class='sbapi__results__url'>${r.url}</div>
              <div class='sbapi__results__kw'>${r.kw}</div>
              <div class='sbapi__results__ranks'>
                <div class='sbapi__results__grank'>${r.grank}</div>
                <div class='sbapi__results__brank'>${r.brank}</div>
                <div class='sbapi__results__yrank'>${r.yrank}</div>
                <div class='sbapi__results__ms'>${r.searchvolume}</div>
              </div>
            </div>
          `);
      })
      // Clears any previous result
      lastUpdatedEl.html('')
      // Present view with data
      lastUpdatedEl.append(`
        <div class='sbapi__results__updated'>
          <h4>Last Updated</h4>
          <span>When: ${lastUpdateObj.when}</span>
          <span>Date: ${lastUpdateObj.date}</span>
        </div>
      `);
    });
  }
})
