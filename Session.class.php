<?php
/**
 * @author:haive
 */
class Session{
    public function __construct(){

        session_set_save_handler();
    }
}