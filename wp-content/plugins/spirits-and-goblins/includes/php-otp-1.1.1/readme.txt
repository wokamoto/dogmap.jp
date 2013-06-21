* ====================== *
* PHP One-Time Passwords *
* ====================== *
Copyright (C) 2009 Tomas Mrozek

Homepage: http://sourceforge.net/projects/php-otp/
Author:	  Tomas Mrozek <mail@cascaval.com>
License:  LGPLv3 (lgpl.txt)


CONTENT
-------
 1. Introduction
 2. What is an one-time password system good for?
 3. One-time password authentication scenario
 4. Devices that can be used for computing one-time passwords
 5. Class usage
    5.1 OTP server
    5.2 OTP generator
 6. Initialization and reinitialization of the OTP sequence
    6.1 Initialization
    6.2 Reinitialization
 7. Main methods
    7.1 initializeOtp()
    7.2 reinitializeOtp()
    7.3 authAgainstHexOtp()
    7.4 generateOtp()
    7.5 generateOtpList()
 8. Other useful methods
    8.1 getAvailableAlgorithms()
    8.2 generateSeed()
    8.3 isValidPassPhrase()
    8.4 isValidSeed()
    8.5 isAvailableAlgorithm()
    8.6 isValidSequence()
    8.7 createChallenge()
    8.8 parseChallenge()
    8.9 reformatHexOtp()
    8.10 setCustomDictionary()
 9. Security considerations
10. Notes
11. Donations


1. INTRODUCTION

"PHP One-Time Passwords" is a PHP implementation of the one-time password
system (OTP) as specified in RFC 2289. It is not a standalone application but
a generic class that is supposed to help PHP developers to implement an one-time
password system in their projects (e.g. a CMS module) either as an OTP server or
as an OTP generator.


2. WHAT IS AN ONE-TIME PASSWORD SYSTEM GOOD FOR?

OTP is an authentication system that is secure against "reply attacks" based on
re-using passwords captured by means of eavesdropping (monitoring data
transferred over the network, software/hardware keylogging, etc.). While
capturing sensitive data during the transfer from a client to the server might
be prevented by using secure connection, possibility of data being captured
during input on a compromised machine (e.g. in an internet cafe) is hard to
prevent and this problem is unlikely to cease to exist anytime soon.

OTP prevents this sort of attacks by using one-time passwords that were
previously generated using a secret pass-phrase. However, users are not required
to store a list of pre-generated one-time passwords. Instead, during each
authentication request they are presented with a challenge (seed and sequence 
number) for which they must compute a response based on their knowledge of
the secret pass-phrase. They are supposed to use an external device
(e.g. a mobile phone) equipped with appropriate software (e.g. a Java applet)
that would compute an one-time password after inputting a server challenge
and the secret pass-phrase.


3. ONE-TIME PASSWORD AUTHENTICATION SCENARIO

STEP 1: While being logged to the system in a SECURE environment (e.g. home
computer), the user supplies the server with an one-time password for the given
seed and number of one-time passwords (sequences; N) that he wants to use.

STEP 2: The server saves the one-time password altogether with the seed, next
challenge sequence number (N-1) and used hash algorithm.

STEP 3: While trying to access the system in an INSECURE environment
(e.g. an internet cafe), the user opts for the login with an one-time password.

STEP 4: The system looks up the user in its database and finds his pre-generated
OTP which it presents to the users as a challenge that consists of the hash
algorithm that is to be used, a sequence number and a seed.

STEP 5: The user starts an OTP generator in his mobile phone, chooses the right
hash algorithm, enters the seed and sequence number obtained from the server,
enters his secret pass-phrase and makes the OTP generator calculate an one-time
password in the form of six words (each having length of 1-4 characters) or
a 16-character long hexadecimal value.

STEP 6: The user enters the generated one-time password (either in the form of
six words or as a 16-character long hexadecimal value) into the form presented
by the system.

STEP 7: The system looks up the user data again and together with the user input
passes them to the OTP class for validation.

STEP 8: If the validation fails, the system presents the user with the same
challenge and the process returns back to the step 5. If the validation
succeeds, the system logs the user in and modifies his data in the database:
decreases the sequence number and stores the OTP provided by the user.


4. DEVICES THAT CAN BE USED FOR COMPUTING ONE-TIME PASSWORDS

Various authentication schemes employ various devices in the authentication
process, e.g. USB tokens, specialized self-powered calculators/tokens. Most of
them preserve some sort of a problem: extra device to carry just for the sake
of authentication, inability to use in some environments (an USB token in
an internet cafe), lack of interoperability between various systems, etc.
However, there are devices that people carry all the time (e.g. mobile phones,
PDAs) and that can be used in the authentication process. There are several
software applications (OTP generators) that can run on mobile phones and that
allow users to compute one-time passwords:

* VeJOTP - a J2ME application for mobile phones.
  Author: Veghead
  URL: http://fatsquirrel.org/software/vejotp/

* J2ME OTP - a J2ME application for mobile phones.
  Author: Marco Ratto
  URL: http://otp-j2me.sourceforge.net/

* j2me-otp - a J2ME application for mobile phones.
  Author: Jan-Frode Myklebust
  URL: http://tanso.net/j2me-otp/

* One-Time Password Generator - a J2ME application for mobile phones.
  URL: http://marcin.studio4plus.com/en/otpgen/

* Opiekey - an one-time password generator for the Android.
  URL: http://android.f00d.nl/opiekey/

* 1Key - an one-time password control for iPhones.
  Author: Rho Software
  URL: http://www.rho.cc/1Key/

* OTP Generator - an one-time password generator for iPhones.
  Author: Dafydd Walters
  URL: http://www.apptism.com/apps/otp-generator

* PalmOTP - an one-time password generator for Palm OS.
  Author: James D. Lin
  URL: http://www.taenarum.com/software/


5. CLASS USAGE

The class provides generic methods needed for creation of an one-time password
authentication system as describe in RFC 2289 and as such can be used both for
an OTP server (initialization, reinitialization, authentication) and
an OTP generator (one-time password generation), the server scenario being more
likely.


5.1 OTP server

Intended server usage takes into account many security aspects:

* Secret pass-phrase is never transferred over the network as the server does 
  not need it for its operations. Therefore, an attacker gaining access to
  the server is unable to get the user's secret pass-phrase.

* Users are not required to store a list of one-time passwords. Instead, OTPs
  are calculated according to the challenge provided by the server. Therefore,
  an attacker cannot gain access to the OTPs.

* The server does not store OTPs so if an attacker gains access to the database,
  he will not be able to get OTPs for future challenges. The server only stores
  the OTP of the last successful login. Getting next OTP out of it would require
  breaking the used hash algorithm. So, if the total sequence count is N,
  the server stores in the database hash for N while during the first
  authentication the user is presented with N-1, after another one with N-2,
  until the count reaches 0 and reinitialization is required.

* The secret pass-phrase is concatenated with a random seed (salt). Therefore,
  even if you use the same pass-phrase on many OTP systems, the stored hashed
  OTPs will be different.

Intended process:

1. While having the user signed to your system in a secure environment, provide
him with the possibility of creating a sequence of one-time passwords. The user
is given (or chooses) a seed, number of OTPs required and the hash algorithm
that will be used during OTP generation and authentication. The user then must
use an external OTP generator to calculate an OTP for the given data.

2. Call the method intializeOtp() and pass the OTP calculated by user along
with the seed, sequence count and hash algorithm. You get a simple backward 
reference array containing data that you will need for authentication of 
the user next time he wishes to authenticate using an OTP.

3. When the user wishes to login to your system with an OTP, look up his data
in the database and provide him with the challenge which consists of the hash
algorithm to be used, the sequence number and the seed. While you MIGHT provide
this information in a pretty human-friendly way, you SHOULD/MUST display
the challenge also as a standardized string. To get correct form of the string,
use the method createChallenge().

4. Having the OTP provided by user, pass it to the authAgainstHexOtp() method
along with the hash algorithm used, sequence number and "previous OTP" which you
have stored. You don't have to worry whether the user input is a hexadecimal
value or a six-word value. The method will test both options.

5. If the authentication fails, provide the user with the challenge again. If
it succeeds, replace the user data with the sequence count and "previous OTP"
that you received from the method (yes, "previous OTP" is actually the OTP that
has just been used).

Obviously, you may implement the class differently but then you must take into
account security issues that might arise from your implementation.


5.2 OTP generator

The class can be used for one-time password generation through the generateOtp()
and generateOtpList() method.


6. INITIALIZATION AND REINITIALIZATION OF THE OTP SEQUENCE

The use of the one-time password system requires it to perform two operations:

* Initialization - creation of the sequence of OTPs that will be used.

* Reinitialization - as the number of OTPs "created" during initialization is
  final and decreasing with each successful authentication, at some point
  the user will run out of available OTPs and creation of a new sequence
  will be needed.


6.1 Initialization

The process is clear: get an OTP for the given number (N) of OTPs and
store this OTP while actually using N-1 for the first authentication. The user
(knowing the seed) that will be used in the sequence of OTPs, calculates the OTP
for the number of OTPs that he wants to use and provides this to the server
which sores this OTP along with the seed and sequence number.


6.2 Reinitialization

The number of OTPs is chosen during the initialization and is decreasing with
each successful authentication. Therefore, at some point the user has
to reinitialize the OTP sequence (while changing the seed or his secret 
pass-phrase) or be unable to authenticate with an OTP next time.
The reinitializeOtp() method provides the possibility of reinitialization.
Basically it's just a wrapper method for initializeOtp() that makes sure
the seed has changed.


7. MAIN METHODS

7.1 initializeOtp(string $userInput, string $seed, int $sequenceCount,
    string $algorithm)

Description:
-----------
Checks if the userInput looks like a valid one-time password and returns
an array with values as a reference.

Parameters:
----------
userInput     - hexadecimal or six-word representation of the one-time
                password for the given sequenceCount and hash algorithm.
seed          - (random) seed that was used for the creation of userInput.
                The seed must pass the check by isValidSeed().
sequenceCount - number of iterations = generated one-time passwords. The count
                must pass the check by isValidSequence() and must not be zero.
algorithm     - chosen hash algorithm. The algorithm must pass the check by
                isAvailableAlgorithm() method.

Return value:
------------
An array containing data of the first/next sequence (= sequenceCount-1):
'next_sequence'    - next sequence number to be used for authentication.
'seed'             - seed (salt) of the one-time password.
'algorithm'        - algorithm used (it's just a backward reference).
'previous_hex_otp' - hexadecimal form of the one-time password of the previous
                     sequence. It's actually just a reference of what the user
                     provided in the userInput.

Examples:
--------
$otp = new otp();
print_r($otp->initializeOtp('BoDe Hop JaKe Stow juT RAP', 'alpha1', 99, 'md5'));
print_r($otp->initializeOtp('5AA3 7A81 F212 146C', 'alpha1', 99, 'md5'));

/* Both method calls would output...
Array
(
    [next_sequence] => 98
    [seed] => alpha1
    [algorithm] => md5
    [previous_hex_otp] => 5aa37a81f212146c
)*/


7.2 reinitializeOtp(string $userInput, string $newSeed, array $oldSeeds,
    int $sequenceCount, string $algorithm)

Description:
-----------
A wrapping function for initializeOtp() which checks whether the seed has
changed.

Parameters:
----------
userInput     - hexadecimal or six-word representation of the one-time
                password for the given sequenceCount and hash algorithm.
newSeed       - (random) seed that was used for the creation of  userInput.
oldSeeds      - an array of seeds that were used in the past and should be
                avoided.
sequenceCount - number of iterations = generated one-time passwords.
algorithm     - chosen hash algorithm.

Return value:
------------
See initializeOtp().


7.3 authAgainstHexOtp(string $userInput, string $masterHexOtp,
    string $masterHexOtpType, int $sequence, string $algorithm)

Description:
-----------
Performs authentication of the one-time password provided by an user.
userInput is compared to hexadecimal value of the OTP used for comparison.

Parameters:
----------
userInput        - a six-word or hexadecimal representation of an OTP.
masterHexOtp     - a hexadecimal representation of an OTP against which user
                   input will be compared. The string must pass the check
                   by isHexOtp() method.
masterHexOtpType - 'previous' = comparison against an OTP of previous sequence,
                   'current'  = comparison against an OTP of current sequence.
sequence         - sequence number. The sequence number must pass the check
                   by isValidSequence().
algorithm        - hash algorithm. The algorithm must pass the check by
                   isAvailableAlgorithm() method.

Return value:
------------
An array containing:
'result => TRUE if authentication succeeded, otherwise FALSE
'otp'   => FALSE if authentication failed, otherwise an array containing
           'next_sequence' => sequence number for the next authentication
           'algorithm' => an echo of the hash algorithm that was used
           'previous_hex_otp' => hexadecimal value of the OTP which is supposed
                                 to be stored and used for comparison next time

Examples:
--------
$otp = new otp();
print_r($otp->authAgainstHexOtp('BoDE HoP jAKE sTOW JUT rAP',
				'07f0dac3f1f24760', 'previous', 99, 'md5'));

/* Results in suceessful authentication
Array
(
    [result] => 1    // === true
    [otp] => Array
        (
            [next_sequence] => 98
            [algorithm] => md5
            [previous_hex_otp] => 5aa37a81f212146c
        )

)*/

print_r($otp->authAgainstHexOtp('MAY STAR TIN LYON VEDA STAN',
				'07f0dac3f1f24760', 'previous', 99, 'md5'));

/* Results in unsuccessful authentication
Array
(
    [result] =>  // === false
    [otp] =>     // === false
)*/


7.4 generateOtp(string $passPhrase, string $seed, int $sequence
    [, string $algorithm])
	
Description:
-----------
Creates an array containing data of the generated one-time password. 

Parameters:
----------
passPhrase - user's secret pass-phrase. The pass-phrase must pass the check
             by isValidPassPhrase() method.
seed       - seed that will be concatenated with the secret pass-phrase.
             The seed must pass the check by isValidSeed().
sequence   - sequence number. The sequence number must pass the check
             by isValidSequence().
algorithm  - chosen hash algorithm. The algorithm must pass the check
             by isAvailableAlgorithm() method.

Return value:
------------
An array containing data of the generated one-time password:
'sequence'           - sequence number.
'seed'               - seed (salt) of the one-time password.
'algorithm'          - algorithm used.
'hex_otp'            - hexadecimal form of the one-time password.
'words_otp'          - six-word form of the one-time password.
'previous_hex_otp'   - hexadecimal form of the previous (= $sequence+1) OTP
'previous_words_otp' - six-word form of the previous (= $sequence+1) OTP
'next_hex_otp'       - hexadecimal form of the next (= $sequence-1) OTP
'next_words_otp'     - six-word form of the next (= $sequence-1) OTP

Examples:
--------
$otp = new otp();
print_r($otp->generateOtp('AbCdEfGhIjK', 'alpha1', 99, 'sha1'));

/* Outputs...
Array
(
    [sequence] => 99
    [seed] => alpha1
    [algorithm] => sha1
    [hex_otp] => 27bc71035aaf3dc6
    [words_otp] => MAY STAR TIN LYON VEDA STAN
    [previous_hex_otp] => 71fb352c76c1daa7
    [previous_words_otp] => DEFT SEWN ALLY TONG INK BASS
    [next_hex_otp] => 6cee8f589a82d2a0
    [next_words_otp] => CUBA DOCK SALT PRO NOW AWRY
)*/


7.5 generateOtpList(string $passPhrase [, string $seed [, array $excludedSeeds
    [, integer $sequenceCount [, string $algorithm]]]])

Description:
-----------
Allows some alternative implementation when the list of all the one-time
passwords in a sequence is actually needed.

Parameters:
----------
passPhrase    - user's secret pass-phrase.
seed          - (random) seed that will be concatenated with the secret
                pass-phrase. The seed must pass the check by isValidSeed().
excludedSeeds - an array of seeds that must not be used.
sequenceCount - number of iterations = generated one-time passwords.
                The sequence number must pass the check by isValidSequence().
algorithm     - chosen hash algorithm. The algorithm must pass the check
                by isAvailableAlgorithm() method.

Return value:
------------
An array of arrays, starting with the first sequence (= sequenceCount-1) to be
used and each of them containing:
'sequence'  - sequence number to be used for authentication.
'seed'      - seed (salt) of the one-time password.
'algorithm' - algorithm used (it's just a backward reference).
'hex_otp'   - hexadecimal form of the one-time password.
'words_otp' - six-word form of the one-time password.

Examples:
--------
$otp = new otp();
print_r($otp->generateOtpList('AbCdEfGhIjK', 'alpha1', null, 100, 'sha1'));

/* Outputs...
Array
(
    [0] => Array
        (
            [sequence] => 99
            [seed] => alpha1
            [algorithm] => sha1
            [hex_otp] => 27bc71035aaf3dc6
            [words_otp] => MAY STAR TIN LYON VEDA STAN
        )

    // one-time passwords for sequence 98 to 1

    [99] => Array
        (
            [sequence] => 0
            [seed] => alpha1
            [algorithm] => sha1
            [hex_otp] => ad85f658ebe383c9
            [words_otp] => LEST OR HEEL SCOT ROB SUIT
        )
)*/


8. OTHER USEFUL METHODS

8.1 getAvailableAlgorithms()

Description:
-----------
Creates a list of hash algorithms that can be used for generation and
authentication of OTPs in the given environment. It will most probably be MD4,
MD5 and SHA1 in all the cases but in theory some of these algorithms might
be unavailable.

Return value:
------------
An array of available hash algorithms.


8.2 generateSeed([int $minLength[, int $maxLength[, array $excludedSeeds]]])

Description:
-----------
The method creates a valid random seed used for generation of the OTPs. It is
used automatically in the generateOtp() method if you don't pass a seed to it.
You might need this method for initializeOtp() and reinitializeOtp() methods
because you need to give the seed to the user prior to using these two methods.

Parameters:
----------
minLength     - minimum length of the seed. Default value is 1.
maxLength     - maximum length of the seed. Default value is 16.
excludedSeeds -	an array of seeds that must not be used anymore.

Return value:
------------
A string which is a seed that conforms to RFC 2289.


8.3 isValidPassPhrase(string $passPhrase)

Description:
-----------
Checks whether the pass-phrase conforms to RFC 2289.

Parameters:
----------
passPhrase - pass-phrase supplied by the user.

Return value:
------------
TRUE if the pass-phrase is valid, otherwise FALSE.


8.4 isValidSeed(string $seed)

Description:
-----------
Checks whether the seed conforms to RFC 2289. Useful if you let the user
supply the seed.

Parameters:
----------
seed - seed supplied by the user.

Return value:
------------
TRUE if the seed is valid, otherwise FALSE.


8.5 isAvailableAlgorithm(string $algorithm)

Description:
-----------
Checks whether the algorithm can be used. While the method isValidAlgorithm()
only checks whether the algorithm conforms to RFC 2289, this method also checks
in a cas-insensitive manner that the algorithm is available in the given PHP
environment.

Parameters:
----------
algorithm - algorithm chosen/entered by an user.

Return value:
------------
TRUE if the algorithm can be used, otherwise FALSE.


8.6 isValidSequence(string $sequence)

Description:
-----------
Checks whether the sequence chosen/supplied by the user is a valid sequence
number.

Parameters:
----------
sequence - sequence supplied by the user.

Return value:
------------
TRUE if the sequence is valid, otherwise FALSE.


8.7 createChallenge(string $seed, int $sequence, string $algorithm)

Description:
-----------
Creates a challenge string that has a standard syntax which can be recognized
by automated tools.

Parameters:
----------
seed      - a seed use in the current challenge.
sequence  - a sequence number of the current challenge.
algorithm - an algorithm used.

Return value:
------------
A challenge string that conforms to RFC 2289.

Examples:
--------
$otp = new otp();
echo $otp->createChallenge('alpha1', '13', 'md5');
// outputs 'otp-md5 13 alpha1 '


8.8 parseChallenge(string $challenge)

Description:
-----------
Parses an RFC 2289 conforming challenge string into its elements.

Parameters:
----------
challenge - a challenge string, e.g "otp-md5 13 alpha1 ".

Return value:
------------
An array of challenge elements:
'algorithm' - 'md4', 'md5' or 'sha1'.
'sequence'  - sequence number.
'seed'      - challenge seed.


8.9 reformatHexOtp(string $hexOtp[, string $letterCase[, string $format]])

Description:
-----------
Takes a hexadecimal one-time password and gives it a standard form. Useful for
converting user input to a standard format or for displaying the OTP in
the format that is easier to read.

Parameters:
----------
hexOtp     - an hexadecimal one-time password.
letterCase - case of the letters of the output format.
format     - '1' = '27BC71035AAF3DC6'
             '2' = '27BC7103 5AAF3DC6'
             '4' = '27BC 7103 5AAF 3DC6'
             '8' = '27 BC 71 03 5A AF 3D C6'

Return value:
------------
A reformatted hexadecimal value of an one-time password.

Examples:
--------
$otp = new otp();
echo $otp->reformatHexOtp('2 7Bc7 10 3 5Aa F3 d C6', 'upper', '4');
// outputs '27BC 7103 5AAF 3DC6'


8.10 setAlternativeDictionary(array $dictionary)

Description:
-----------
Sets an alternative dictionary to be used for generation of OTPs and
authentication. The dictionary must pass isValidDictionary() method in order
to be used.

Parameters:
----------
dictionary - an array of 2048 words that will be used instead of
             the standard dictionary.

Return value:
------------
TRUE if the dictionary was set, otherwise FALSE.


9. SECURITY CONSIDERATIONS

RFC 2289 One-Time Password System is a nice concept that solves many issues
of normal password-based authentication process. However, this concept is not
bullet-proof and there are issues which you have to be aware of:

* Race attack - "It is possible for an attacker to listen to most of a one-time
  password, guess the remainder, and then race the legitimate user to complete
  the authentication."

* Man in the middle attack - an attacker capable of real-time capturing and
  modifying data transferred between the user and the server can gain access
  to the system if the data are not encrypted.

* Session hijacking (e.g. theft of a magic cookie) - the OTP does not provide
  any security against this as this is an active attack and not a reply attack.

* OTP sequence repeat - in other words re-using OTPs previously used. This might
  happen due to an user being allowed to set the same pass-phrase and seed, or
  it might accidentaly happen during a database rollback (for eaxample when
  restoring database from the backup).

* Human stupidity - it's nice that the user authenticates with an one-time
  password. However, if he uses an one-time password generator (e.g. desktop
  software, web-based generator) that resides on (or is accessed through)
  the very same computer that he uses to log in, then the whole OTP concept gets
  useless. The user must use an another (external) trusted device for generation
  of OTPs.


10. NOTES

* Before implementing the class, you should be familiar with RFC 2289. Reading
  this "readme.txt" might not be enough.

* Number of iterations (sequence count) - you should limit the user to choose
  a reasonable sequence count. Default value is 100. 10000 is alright but
  processing of 100000 may take quite some time during OTP generation.

* For each sequence of one-time passwords there MUST be a different secret
  pass-phrase OR a different seed. Obviously, it's somewhat more convenient
  to change the seed and keep the pass-phrase same.

* Alternative dictionaries - although the class can use an alternative
  dictionary, the support is somewhat limited:

  1. The server should be able to accept any alternative dictionary without
     having access to such a dictionary. It should compute an 11-bit number of
     every word by applying this algorithm: alg( W ) % 2048 == N
     Unfortunately, no example is given and as alg can be any accepted hash
     algorithm, it is unclear how the server is supposed to know which hash
     was used for generation of the dictionary.

  2. The server is supposed to disregard the case of letters in the user input.
     In other words, 'BoDe Hop JaKe Stow juT RAP' should be interpreted as
     'BODE HOP JAKE STOW JUT RAP'. However, the words in the alternative
     dictionaries are supposed to be case-sensitive which means that the server
     would have to know that an alternative dictionary is being used and
     therefore that it must accept the user input in a case-sensitive manner.


11. DONATIONS

I don't take monetary donations but if you feel that this class significantly
helped you in your (commercial) project, have a look at my Amazon wishlist:
http://www.amazon.co.uk/gp/registry/23QVWTT1PC6U3