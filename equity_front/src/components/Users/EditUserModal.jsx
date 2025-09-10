import { useState, useEffect } from "react";
import Swal from "sweetalert2";
import { isAdmin } from "../../utils/utils";

export default function EditUserModal({
  user,
  onClose,
  onSave,
  allPermissions,
  currentUser,
}) {
  const [name, setName] = useState(user.name);
  const [email, setEmail] = useState(user.email);
  const [password, setPassword] = useState("");
  const [permissions, setPermissions] = useState(
    user.permissions.map((p) => (typeof p === "string" ? p : p.name))
  );

  const editingAdmin = isAdmin(user);
  const currentAdmin = isAdmin(currentUser);

  useEffect(() => {
    setName(user.name);
    setEmail(user.email);
    setPermissions(
      user.permissions.map((p) => (typeof p === "string" ? p : p.name))
    );
    setPassword("");
  }, [user]);

  const handleTogglePermission = (perm) => {
    setPermissions((prev) =>
      prev.includes(perm) ? prev.filter((p) => p !== perm) : [...prev, perm]
    );
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    // Validación de permisos sobre admin
    if (password && editingAdmin && !currentAdmin) {
      Swal.fire(
        "Error",
        "Solo un admin puede cambiar la contraseña de otro admin",
        "error"
      );
      return;
    }

    const payload = { name, email };
    if (password) payload.password = password;

    // Solo actualizar permisos si no es admin o si el currentUser es admin
    if (!editingAdmin || currentAdmin) {
      payload.permissions = permissions;
    }

    await onSave(user.id, payload);
    onClose();
  };

  return (
    <div className="modal-overlay">
      <div className="modal-content">
        <h3>Editar Usuario: {user.name}</h3>
        <form onSubmit={handleSubmit} className="modal-form">
          <div className="form-group">
            <label>Nombre:</label>
            <input
              value={name}
              onChange={(e) => setName(e.target.value)}
              disabled={editingAdmin && !currentAdmin}
            />
          </div>

          <div className="form-group">
            <label>Email:</label>
            <input
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              disabled={editingAdmin && !currentAdmin}
            />
          </div>

          <div className="form-group">
            <label>Password:</label>
            <input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="Dejar vacío si no cambia"
            />
          </div>

          <div className="form-group">
            <label>Permisos:</label>
            <div className="permissions-list">
              {allPermissions.map((perm) => {
                const checked = editingAdmin
                  ? true
                  : permissions.includes(perm);
                const disabled = editingAdmin || user.id === currentUser.id;
                return (
                  <label key={perm} className="permission-item">
                    <input
                      type="checkbox"
                      checked={checked}
                      onChange={() => handleTogglePermission(perm)}
                      disabled={disabled}
                    />
                    {perm}
                  </label>
                );
              })}
            </div>
          </div>

          <div className="buttons">
            <button type="submit">Guardar</button>
            <button type="button" onClick={onClose}>
              Cancelar
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
