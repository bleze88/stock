-- Verrouillage de compte apres echecs de connexion repetes : contrairement
-- au verrouillage temporaire deja en place (login_attempts, base sur IP,
-- expire tout seul), ce verrou est persistant et ne peut etre leve que
-- par un admin depuis la page Utilisateurs.
ALTER TABLE users ADD COLUMN failed_attempts INTEGER NOT NULL DEFAULT 0;
ALTER TABLE users ADD COLUMN locked INTEGER NOT NULL DEFAULT 0;
