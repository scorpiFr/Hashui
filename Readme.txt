Hashui is a php nosql key => value service.
1 key = 1 value in 1 file.
Files are stored in a bunch of repertories and sub-repertories.
Hashui is made to store middle sized files.


Ex (if you store a key): 
$contents = @file_get_contents("/etc/hosts");
$hui = new Hashui("/path/to/rootStorageDir");
$key = $hui->set($contents);
print($hui->get($key));


Ex2 (if you don't want to store keys) :
$filePath = '/etc/hosts';
$contents = @file_get_contents($filePath);
$userId = 198212;
$key = "user:$userId;";
$value = "{filename: $filePath; contents: $contents}";
$hui = new Hashui("/path/to/rootStorageDir");
$hui->set($value, $key);
print($hui->get($key));


