from flask import *
from flask_sqlalchemy import SQLAlchemy
import crypto, hashlib

app = Flask("the1024club")
app.config["SQLALCHEMY_DATABASE_URI"] = "sqlite:///the1024.db"
db = SQLAlchemy(app)


class User(db.Model):
    __tablename__ = "the1024"
    id = db.Column(db.Integer, primary_key=True)
    fingerprint = db.Column(db.Text, nullable=False, unique=True)
    pubkey = db.Column(db.LargeBinary, nullable=False)
    data = db.Column(db.LargeBinary)
    mime_type = db.Column(db.Text, default="text/plain; charset=UTF-8")


db.create_all()


@app.route("/")
def home():
    return "TODO: implement home page"


@app.route("/register", methods=["GET", "POST"])
def register():
    if request.method == "GET":
        return "TODO: registration info page (registration form?)"
    pubkey = request.files.get("public_key")
    if not pubkey:
        response = make_response(
            jsonify(dict(error="Provide a public key as file `public_key`.")),
            400)
        response.headers["Content-Type"] = "application/json"
        return response
    pubkey = pubkey.read()
    if not crypto.supports(pubkey):
        response = make_response(
            jsonify(
                dict(error="Unsupported public key type. Supported types: " +
                     (", ".join([
                         f.__name__.replace("PublicKey", "")
                         for f in crypto.SUPPORTED_PUBLIC_KEY_FORMATS
                     ])))), 400)
        response.headers["Content-Type"] = "application/json"
        return response
    fingerprint = hashlib.sha256(pubkey).hexdigest()
    # is there already a hash with that fingerprint in the database?
    if User.query.filter_by(fingerprint=fingerprint).all():
        response = make_response(
            jsonify(
                dict(
                    error=
                    "A public key with this hash already exists in the database."
                )), 400)
        response.headers["Content-Type"] = "application/json"
        return response
    # do the magic
    new_user = User(fingerprint=fingerprint, pubkey=pubkey)
    db.session.add(new_user)
    db.session.commit()
    response = make_response(
        jsonify(
            dict(
                error=None,
                url=url_for("render_data", fingerprint=fingerprint))), 200)
    response.headers["Content-Type"] = "application/json"
    return response


@app.route("/<fingerprint>", methods=["GET", "UPDATE", "DELETE"])
def render_data(fingerprint):
    if request.method == "GET":
        user = User.query.filter_by(fingerprint=fingerprint).first()
        if not user: return abort(404)
        resp = make_response(user.data or b"", 200)
        resp.headers["Content-Type"] = user.mime_type
        return resp
    return abort(501)


if __name__ == "__main__":
    app.run(port=65532)
