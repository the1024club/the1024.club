"""The cryptography backend for the1024.club. Here lie dragons.

Everything will be explained to the best of my ability, but please understand
that even I am lost about how most of these functions work.

At the moment, we only support RSA public keys in the backend. This is because
finding the right parameters for signature verification is like shoving bamboo
under my fingernails (that is to say, extremely painful), and I don't want to
do it any more than necessary. If you want another public key format to be
implemented, figure it out your own damn self and submit a pull request.

Sincerely yours,
Robert Miles
~khuxkm

P.S. This code (at least, the parts written by me) is licensed under MIT, in
addition to whatever license the rest of the1024.club's code is licensed under."""
from cryptography.hazmat.backends import default_backend
from cryptography.hazmat.primitives.serialization import load_pem_public_key
from cryptography.hazmat.primitives.asymmetric import padding, rsa
from cryptography.hazmat.primitives.hashes import SHA256
from cryptography.exceptions import InvalidSignature
"""Key formats we support. For a class to be in here, it must have a corresponding isinstance() check in verify_signature."""
SUPPORTED_PUBLIC_KEY_FORMATS = [rsa.RSAPublicKey]


class UnknownKeyFormat(Exception):
    pass


def verify_signature(block_data, signature, pubkey):
    """Verifies a signature `signature`, given data `block_data` and a public key `pubkey`.
    
    To generate a signature that can be verified by this function, use:
    $ openssl dgst -sha256 -sign private.key -sigopt rsa_padding_mode:pss -sigopt rsa_pss_saltlen=32 -out <signature> <file>"""
    # load the public key
    public_key = load_pem_public_key(pubkey, backend=default_backend())
    # is it an RSA public key?
    if isinstance(public_key, rsa.RSAPublicKey):
        try:
            # public_key.verify raises InvalidSignature on verification failure, so call it
            public_key.verify(
                signature,
                block_data,
                # PSS padding, with SHA256-digest MGF-1 and a salt length of 32
                padding.PSS(padding.MGF1(SHA256()), 32),
                # SHA256 digest
                SHA256())
            return True
        except InvalidSignature:
            return False
    raise UnknownKeyFormat(type(public_key).__name__)


def supports(pubkey):
    """Returns whether public key `pubkey` is of a supported type."""
    # load the public key
    public_key = load_pem_public_key(pubkey, backend=default_backend())
    # if public_key is an instance of any of SUPPORTED_PUBLIC_KEY_FORMATS, we support it
    return any([
        isinstance(public_key, format)
        for format in SUPPORTED_PUBLIC_KEY_FORMATS
    ])
