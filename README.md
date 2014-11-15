AESSeizurePrediction
====================
This is a project orignally for a compettion on Kaggle.com
However, having realized there were no Matlab libraries for PHP, I decided to write one myself. Taking the .mat files from the competition as examples I wrote some code that can properly parse them. I haven't tested it with any other .mat files, but it's possible it could work.
Having written the library I decided to go forward with the implementation to see how well I could do. That's where it's at now.

Requirements
====================
* PHP 5.5+
* pecl's SVM library, along with libsvm installed
* .mat files provided by the competition
* Probably some other stuff

TODO
====================
* Add ability to add to .sav instead of writing it from scratch
* Improve scores
* Test more than Dog_5