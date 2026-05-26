<p>Password protection is available on <strong>Plus</strong> and higher plans (Pro + Wedding).</p>
<ol>
    <li>In the panel open your list Settings.</li>
    <li>In the "Access password" section enter at least 4 characters and save.</li>
    <li>From now on every visitor must enter the password before seeing the list.</li>
</ol>
<p>The password is hashed (bcrypt) — we never store it in readable form.</p>
<p>To remove protection, tick <em>"Remove password"</em> in the same settings.</p>
<p>Wrong-password attempts are rate-limited to 5 per minute per IP — brute-force protection.</p>
