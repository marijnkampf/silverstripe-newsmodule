	<section id="latest_work" class="clear">
      <% loop NewsArchive(3) %> <%-- Check code/extensions/NewsExtension.php for configuration --%>
      <article class="one_third $FirstLast"><% if Impression %><a href="$Link" class="impressionLink"><% with Impression %>$SetSize(50,50)<% end_with %></a><% end_if %>
          <% if Type == external %>
			<h2><a href='$External' target="_blank">$Title</a></h2>
		<% else_if Type == download %>
			<h2><a href='$Download.Link' title='Downloadable file'>$Title (<%t NewsHolderPage.ss.DOWNLOADABLE "Download" %>)</a></h2>
		<% else %>
			<h2><a href="$Link">$Title</a></h2>
		<% end_if %>
	  <h3>$Author</h3>
	<i><%t NewsHolderPage.ss.DATEPUBLISH "{date} by {author}"  date=$Published author=$Author %></i>
	  <% if Synopsis %>
	  <p>$Synopsis</p>
	  <% else %>
          <p>$Content.Summary</p>
	  <% end_if %>
	  <% if Tags.Count > 0 %>
	  <br />
	  <div class="small">
		<% loop Tags %>
		<a href="{$Top.URLSegment}/tag/$URLSegment">$Title</a><% if Last %><% else %>&nbsp;|&nbsp;<% end_if %>
		<% end_loop %>
	  </div>
	  <br />
	  <% end_if %>
          <footer class="more"><a href="$Link"><%t NewsHolderPage.ss.READMORE "Read More &raquo;" %></a></footer>
        </article>
	      <% end_loop %>

      </section>