<?php
class Person {
    protected $name;

    function set_name($new_name) {
        $this->name = $new_name;
    }

    function get_name() {
        return $this->name;
    }
}

$person1 = new Person();
$person1->set_name('Lukman Hakim');
echo $person1->get_name();
echo "<br>";

$person1->set_name('Taufiq Rizaldi');
echo "Hai " . $person1->get_name();
echo "<hr>";
?>
