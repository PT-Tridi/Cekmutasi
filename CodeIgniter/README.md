# Cekmutasi X CodeIgniter Framework
Development &amp; Integration Toolkit for CodeIgniter Framework

Just simple step, copy **libraries/Cekmutasi.php** to your **application/libraries**

## How To Use?

Edit and set your Api Key in **libraries/Cekmutasi.php**

You can use cekmutasi library by loading Cekmutasi through CodeIgniter Library Loader as shown below:

<pre><code>$this-&gt;load-&gt;library(&quot;cekmutasi&quot;);</code></pre>

it will load Cekmutasi library in CodeIgniter class instance so you can use like this:

<pre><code>$mutations = $this-&gt;cekmutasi-&gt;balance();</code></pre>

Object data will return as data type of result

For further example, you can check out in TestCekmutasi.php included on this package
