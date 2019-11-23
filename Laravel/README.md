# Cekmutasi X Laravel Framework
Development &amp; Integration Toolkit for Laravel Framework (5.3+)

## Steps
- Copy **packages** folder under your laravel root directory, or you can skip to **packages/pt-tridi** if **packages** folder already exists (http://prntscr.com/lbg8pl)
- Open your **config/app.php** and add this code to the providers array, it will looks like:

<pre><code>'providers' =&gt; [

      // other providers

      PTTridi\Cekmutasi\CekmutasiServiceProvider::class,

],</code></pre>

- Add this code to your class aliases array

<pre><code>'aliases' =&gt; [

      // other aliases

      'Cekmutasi' => PTTridi\Cekmutasi\CekmutasiFacade::class,

],</code></pre>

- Open your **composer.json** in the root folder then add this code to the psr-4 on autoload section, it will looks like:

<pre><code>&quot;autoload&quot;: {

        // other section
        
        &quot;psr-4&quot;: {
            &quot;App\\&quot;: &quot;app/&quot;,
            &quot;PTTridi\\Cekmutasi\\&quot;: &quot;packages/pt-tridi/cekmutasi/src&quot;,
        },
        
       // other section
       
},</code></pre>

- Then run composer command on your Command Line Console

<pre><code>composer dump-autoload</code></pre>

- Add **CEKMUTASI_API_KEY** & **CEKMUTASI_API_SIGNATURE** to your **.env** file, it will looks like

<pre><code>// other env variable
	
CEKMUTASI_API_KEY="place your api key here"
CEKMUTASI_API_SIGNATURE="place your api signature here"

 </code></pre>

## How To Use?

You can use cekmutasi library by importing cekmutasi class. Here is the example of using cekmutasi class in Controller

<pre><code>&#x3C;?php

namespace App\Http\Controllers;

use PTTridi\Cekmutasi\Cekmutasi;

class AnotherController extends Controller
{
    $mutation = Cekmutasi::bank()-&#x3E;search([
				'date'		=&gt; [
					'from'	=&gt; date('Y-m-d') . ' 00:00:00',
					'to'	=&gt; date('Y-m-d') . ' 23:59:59'
				]
			]);

    dd($mutation);
}

?&#x3E;</code></pre>

For further example, you can check out in **CekmutasiController.php** included in this package
