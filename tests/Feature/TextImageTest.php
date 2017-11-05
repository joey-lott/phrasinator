<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\RPL\TextImage as Image;
use Illuminate\Support\Facades\Storage;

class TextImageTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_can_set_text()
    {
      $text = "test text";
      $image = new Image($text, "knockout.ttf");
      $this->assertEquals($text, $image->text);

    }

    public function test_can_get_longest_word()
    {
      $text = "a ab abc abcd abcde abcdef";
      $image = new Image($text, "knockout.ttf");
      $this->assertEquals("abcdef", $image->longestWord());


      $text = "abcd ab abcde abcdef a abc ";
      $image = new Image($text, "knockout.ttf");
      $this->assertEquals("abcdef", $image->longestWord());
//      $loaded = $image->loadFont("knockout.ttf");
//      $this->assertTrue($loaded);
    }

    public function test_can_get_dimensions_of_each_word() {

      // This test assumes the default font size (400)
      $text = "a ab abc abcd abcde abcdef";
      $image = new Image($text, "knockout.ttf");
      $sizes = $image->getWordsDimensions();

      $this->assertEquals(6, count($sizes));
    }

    public function test_can_get_total_width_of_text() {

      // This test assumes the default font size (400)
      $text = "a ab abc abcd abcde abcdef";
      $image = new Image($text, "knockout.ttf");
      $dimensions = $image->getTotalDimensionsOfTextInOneLine();

      $this->assertTrue($dimensions["height"] > 10);
      $this->assertTrue($dimensions["width"] > 10);
    }

    public function test_can_arrange_words_to_lines() {
      // This test assumes the default font size (400)
      $text = "a ab abc abcd abcde abcdef";
      $image = new Image($text, "knockout.ttf");
      $lines = $image->arrangeWordsToLines();

      $text = "the quick brown fox jumped over the lazy dog";
      $image = new Image($text, "knockout.ttf");
      $lines = $image->arrangeWordsToLines();

      $text = "the quick brown fox jumped over the lazy dog. The lazy motherfucking dog. Mississippimississippi. That lazy, lazy, lazy dog. Oh so lazy. Lazy lazy lazy. So, so, so very lazy, that lazy lazy dog.";
      $image = new Image($text, "knockout.ttf");
      $lines = $image->arrangeWordsToLines();

      $this->assertTrue(count($lines) > 0);

    }


    public function test_can_arrange_words_to_lines_with_special_characters() {
      // This test assumes the default font size (400)
      $text = "a ab abc abcd:::abcde abcdef";
      $image = new Image($text, "knockout.ttf");
      $lines = $image->arrangeWordsToLines();
      $this->assertTrue(count($lines) == 2);

      $text = "the quick brown:::fox jumped over:::the lazy dog";
      $image = new Image($text, "knockout.ttf");
      $lines = $image->arrangeWordsToLines();
      $this->assertTrue(count($lines) == 3);

      $text = "a:::b:::c:::d";
      $image = new Image($text, "knockout.ttf");
      $lines = $image->arrangeWordsToLines();
      $this->assertTrue(count($lines) == 4);

    }

    public function test_can_get_longest_line() {
      // This test assumes the default font size (400)
      $text = "a ab abc abcd abcde abcdef";
      $image = new Image($text, "knockout.ttf");
      $line = $image->longestLine();
      $this->assertEquals($line, "a ab abc");

      $text = "the quick brown fox jumped over the lazy dog";
      $image = new Image($text, "knockout.ttf");
      $line = $image->longestLine();
      $this->assertEquals($line, "jumped over");

      $text = "the quick brown fox jumped over the lazy dog. The lazy motherfucking dog. Mississippimississippi. That lazy, lazy, lazy dog. Oh so lazy. Lazy lazy lazy. So, so, so very lazy, that lazy lazy dog.";
      $image = new Image($text, "knockout.ttf");
      $line = $image->longestLine();
      $this->assertEquals($line, "That lazy, lazy, lazy dog. Oh");

    }

    public function test_can_adjust_font_to_fill_space() {
      // This test assumes the default font size (400)
      $text = "a ab abc abcd abcde abcdef";
      $image = new Image($text, "knockout.ttf");
      $fontSize = $line = $image->adjustFontToFillSpace();
      $this->assertTrue($fontSize > 0);
    }

    public function test_can_save_image_file()
    {
      $text = strtoupper("a ab abc abcd abcde abcdef mississippimississippimississippimissippimississippimississippimississippimississippimissippimississippi");
      $image = new Image($text, "knockout.ttf");
      $printText = $image->adjustFontToFillSpace();
      $image->saveImage("test.png");


      $text = strtoupper("The Rain In Spain Falls Mainly On The Plain");
      $image = new Image($text, "knockout.ttf");
      $printText = $image->adjustFontToFillSpace();
      $image->saveImage("test2.png");
    }

    // public function test_can_get_calculate_font_size_for_small_words()
    // {
    //   $text = "a ab abc abcd abcde abcdef";
    //   $image = new Image($text, "knockout.ttf");
    //
    //   $this->assertEquals(1528, $image->fontSize());
    // }

/*
    public function test_can_get_calculate_font_size_for_large_words()
    {
      $text = "a ab abc abcd abcde abcdef mississippimississippimississippimissippimississippimississippimississippimississippimissippimississippi";
      $image = new Image($text, "knockout.ttf");

      // The longest word should result in a font size of 97
      $this->assertEquals(97, $image->fontSize());
    }

    public function test_can_split_text_into_lines()
    {
      $text = "a ab abc abcd abcde abcdef mississippimississippimississippimissippimississippimississippimississippimississippimissippimississippi";
      $image = new Image($text, "knockout.ttf");
      $printText = $image->splitToLines();
      $shouldBeText = ["a ab abc abcd abcde abcdef","mississippimississippimississippimissippimississippimississippimississippimississippimissippimississippi"];
      $this->assertEquals($printText, $shouldBeText);


      $text = "the rain in spain falls mainly on the plain";
      $image = new Image($text, "knockout.ttf");
      $printText = $image->splitToLines();
      $shouldBeText = ["the rain in spain falls","mainly on the plain"];
      $this->assertEquals($printText, $shouldBeText);

    }

    public function test_can_save_image_file()
    {
      $text = strtoupper("a ab abc abcd abcde abcdef mississippimississippimississippimissippimississippimississippimississippimississippimissippimississippi");
      $image = new Image($text, "knockout.ttf");
      $printText = $image->splitToLines();
      $image->saveImage("test.png");
      $this->assertTrue(Storage::exists("test.png"));


      $text = strtoupper("The Rain In Spain Falls Mainly On The Plain");
      $image = new Image($text, "knockout.ttf");
      $printText = $image->splitToLines();
      $image->saveImage("test2.png");
      $this->assertTrue(Storage::exists("test2.png"));
    }

    public function test_returns_longest_word_or_line_depending_on_presence_of_special_characters()
    {
      $text = strtoupper("ABCD EFG HIJKLMNOP QRS TUV WXYZ");
      $image = new Image($text, "knockout.ttf");
      $value = $image->longestLine();
      $this->assertEquals($value, "HIJKLMNOP");

      $text = strtoupper("ABCD EFG:::HIJKLMNOP:::QRS TUV WXYZ");
      $image = new Image($text, "knockout.ttf");
      $value = $image->longestLine();
      $this->assertEquals($value, "QRS TUV WXYZ");

    }
*/
}
