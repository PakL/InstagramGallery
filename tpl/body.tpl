		{ISSET:HEADLINE}<h1><a href="index.php">{VAR:HEADLINE}</a></h1>{/ISSET:HEADLINE}
		<table style="width:100%">
			<tr>{ISSET:username}
				<td width="150" valign="top">
					<img src="{VAR:profile_picture}" width="100" height="auto" alt=""><br>
					{ISSET:website}<a href="{VAR:website}" taget="_blank">{/ISSET:website}{VAR:username}{ISSET:website}</a>{/ISSET:website}<br>
					{ISSET:bio}{VAR:bio}<br>{/ISSET:bio}
					<br>
					Bilder: {VAR:pictures}
				</td>
			{/ISSET:username}
				<td valign="top" rowspan="2">
					{VAR:gallery}
				</td>
			</tr>
			<tr><td>{INC:customsidebar.tpl}</td></tr>
			<tr>
				<td style="text-align:left;">{VAR:prev_page}</td>
				<td style="text-align:right;">{VAR:next_page}</td>
			</tr>
		</table>