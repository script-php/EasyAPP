<?php

class ControllerOther extends Controller {
    public function index() {
        pre("Other controller method called successfully!");
        echo "<p>This is output from the 'other' controller.</p>";
    }
}