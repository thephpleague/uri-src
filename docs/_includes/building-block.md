~~~php
use League\Uri\Components\Query;
use League\Uri\Modifier;
use League\Uri\Uri;

$uri = Uri::new('http://eXamPLe.com?q=value#fragment');
$uri->getScheme(); // returns 'http'
$uri->getHost();   // returns 'example.com'

$newUri = Modifier::from($uri)->appendQuery('q=new.Value');
echo $newUri; // 'http://example.com?q=value&q=new.Value#fragment'

$query = Query::fromUri($newUri);
$query->get('q');       // returns 'value'
$query->getAll('q');    // returns ['value', 'new.Value']
$query->parameter('q'); // returns 'new.Value'
~~~

The libraries manipulate URIs and their components using a simple yet expressive code.
