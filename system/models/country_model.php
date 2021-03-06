<?php

/**
 * OVERLOADING NAILS' MODELS
 *
 * Note the name of this class; done like this to allow apps to extend this class.
 * Read full explanation at the bottom of this file.
 *
 **/

class NAILS_Country_model extends NAILS_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->config->load('system/countries');
    }

    /**
     * COUNTRY METHODS
     */

    public function get_all()
    {
        return $this->config->item('countries');
    }

    // --------------------------------------------------------------------------

    public function get_all_flat()
    {
        $out       = array();
        $countries = $this->get_all();

        foreach ($countries as $c) {

            $out[$c->code] = $c->label;
        }

        return $out;
    }

    // --------------------------------------------------------------------------

    public function get_by_code($code)
    {
        $countries = $this->get_all();

        return ! empty($countries[$code]) ? $countries[$code] : false;
    }

    // --------------------------------------------------------------------------
    //  CONTINENT METHODS
    // --------------------------------------------------------------------------


    public function get_all_continents()
    {
        return $this->config->item('continents');
    }

    // --------------------------------------------------------------------------

    public function get_all_continents_flat()
    {
        return $this->get_all_continents();
    }

    // --------------------------------------------------------------------------

    public function get_continent_by_code($code)
    {
        $continents = $this->get_all();

        return ! empty($continents[$code]) ? $continents[$code] : false;
    }
}


// --------------------------------------------------------------------------


/**
 * OVERLOADING NAILS' MODELS
 *
 * The following block of code makes it simple to extend one of the core
 * models. Some might argue it's a little hacky but it's a simple 'fix'
 * which negates the need to massively extend the CodeIgniter Loader class
 * even further (in all honesty I just can't face understanding the whole
 * Loader class well enough to change it 'properly').
 *
 * Here's how it works:
 *
 * CodeIgniter instantiate a class with the same name as the file, therefore
 * when we try to extend the parent class we get 'cannot redeclare class X' errors
 * and if we call our overloading class something else it will never get instantiated.
 *
 * We solve this by prefixing the main class with NAILS_ and then conditionally
 * declaring this helper class below; the helper gets instantiated et voila.
 *
 * If/when we want to extend the main class we simply define NAILS_ALLOW_EXTENSION
 * before including this PHP file and extend as normal (i.e in the same way as below);
 * the helper won't be declared so we can declare our own one, app specific.
 *
 **/

if (! defined('NAILS_ALLOW_EXTENSION_COUNTRY_MODEL')) {

    class Country_model extends NAILS_Country_model
    {
    }
}
