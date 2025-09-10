import "../styles/Users.css";
import { useState, useEffect } from "react";
import Swal from "sweetalert2";
import EditUserModal from "./EditUserModal";
import { isAdmin, apiFetch } from "../../utils/utils";
import NewUser from "./NewUser";

export default function UserAdmin({ currentUser }) {
  const [users, setUsers] = useState([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(false);
  const [editingUser, setEditingUser] = useState(null);
  const [newUser, setNewUser] = useState({ name: "", email: "", password: "" });

  const isCurrentAdmin = isAdmin(currentUser);
  const allPermissions = ["view-invoices", "upload-invoices", "manage-users"];

  // ================= FETCH USERS =================
  const fetchUsers = async (page = 1) => {
    setLoading(true);
    try {
      const data = await apiFetch(
        `http://localhost:8000/api/users?page=${page}`
      );
      setUsers(data.data);
      setPage(data.current_page);
      setLastPage(data.last_page);
    } catch (err) {
      Swal.fire("Error", err.message, "error");
    }
    setLoading(false);
  };

  useEffect(() => {
    fetchUsers(page);
  }, [page]);

  // ================= CREATE USER =================
  const handleCreate = async (e) => {
    e.preventDefault();
    try {
      await apiFetch("http://localhost:8000/api/users", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(newUser),
      });
      Swal.fire("Éxito", "Usuario creado correctamente", "success");
      setNewUser({ name: "", email: "", password: "" });
      fetchUsers(page);
    } catch (err) {
      Swal.fire("Error", err.message, "error");
    }
  };

  // ================= DELETE USER =================
  const handleDelete = async (user) => {
    if (isAdmin(user)) {
      Swal.fire("Error", "No se puede borrar un administrador", "error");
      return;
    }

    const confirm = await Swal.fire({
      title: "¿Eliminar usuario?",
      showCancelButton: true,
      icon: "warning",
    });

    if (confirm.isConfirmed) {
      try {
        await apiFetch(`http://localhost:8000/api/users/${user.id}`, {
          method: "DELETE",
        });
        Swal.fire("Eliminado", "Usuario eliminado correctamente", "success");
        fetchUsers(page);
      } catch (err) {
        Swal.fire("Error", err.message, "error");
      }
    }
  };

  // ================= UPDATE USER =================
  const handleUpdate = async (id, data) => {
    try {
      await apiFetch(`http://localhost:8000/api/users/${id}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data),
      });
    } catch (err) {
      Swal.fire("Error", err.message, "error");
    }
  };

  // ================= ASSIGN PERMISSIONS =================
  const handleAssignPermissions = async (id, permissions) => {
    try {
      await apiFetch(`http://localhost:8000/api/users/${id}/permissions`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ permissions }),
      });
    } catch (err) {
      Swal.fire("Error", err.message, "error");
    }
  };

  return (
    <div className="flex small-col gap-4">
      <div className="card flex-grow flex flex-col gap-4">
        <h2>Administración de Usuarios</h2>
        {loading ? (
          <p>Cargando...</p>
        ) : (
          <>
            <UserTable
              users={users}
              allPermissions={allPermissions}
              isCurrentAdmin={isCurrentAdmin}
              onDelete={handleDelete}
              onEdit={setEditingUser}
            />

            <Pagination
              page={page}
              lastPage={lastPage}
              setPage={setPage}
              loading={loading}
            />

            {editingUser && (
              <EditUserModal
                user={editingUser}
                currentUser={currentUser}
                allPermissions={allPermissions}
                onClose={() => setEditingUser(null)}
                onSave={async (id, data) => {
                  if (
                    !isCurrentAdmin &&
                    id !== currentUser.id &&
                    data.password
                  ) {
                    Swal.fire(
                      "Error",
                      "Solo los administradores pueden cambiar contraseñas de otros",
                      "error"
                    );
                    return;
                  }

                  try {
                    if (data.permissions) {
                      await handleAssignPermissions(id, data.permissions);
                    }

                    await handleUpdate(id, data);

                    Swal.fire(
                      "Éxito",
                      "Usuario actualizado correctamente",
                      "success"
                    );

                    fetchUsers(page);
                  } catch (err) {
                    Swal.fire("Error", err.message, "error");
                  }
                }}
              />
            )}
          </>
        )}
      </div>
      <NewUser
        handleCreate={handleCreate}
        newUser={newUser}
        setNewUser={setNewUser}
      />
    </div>
  );
}

function UserTable({
  users,
  allPermissions,
  isCurrentAdmin,
  onDelete,
  onEdit,
}) {
  return (
    <table className="brand-table">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Email</th>
          <th>Roles</th>
          <th>Permisos</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        {users.map((user) => {
          const isAdminUser = isAdmin(user);
          const canEdit = isCurrentAdmin || !isAdminUser;
          const canDelete = !isAdminUser;

          return (
            <tr key={user.id}>
              <td>{user.name}</td>
              <td>{user.email}</td>
              <td>
                {user.roles
                  .map((r) => (typeof r === "string" ? r : r.name))
                  .join(", ")}
              </td>
              <td>
                {isAdminUser
                  ? allPermissions.join(", ")
                  : user.permissions.map((p) => p.name).join(", ")}
              </td>
              <td>
                <div className="buttons">
                  {canDelete && (
                    <button onClick={() => onDelete(user)}>Eliminar</button>
                  )}
                  {canEdit && (
                    <button
                      onClick={() => {
                        if (isAdminUser && !isCurrentAdmin) {
                          Swal.fire(
                            "Error",
                            "No puedes editar a un administrador",
                            "error"
                          );
                          return;
                        }
                        onEdit(user);
                      }}
                    >
                      Editar
                    </button>
                  )}
                </div>
              </td>
            </tr>
          );
        })}
      </tbody>
    </table>
  );
}

function Pagination({ page, lastPage, setPage, loading }) {
  return (
    <div style={{ marginTop: "1rem" }} className="pagination">
      <button
        onClick={() => setPage((p) => Math.max(p - 1, 1))}
        disabled={page === 1 || loading}
      >
        Anterior
      </button>
      <span style={{ margin: "0 1rem" }}>
        {page} / {lastPage}
      </span>
      <button
        onClick={() => setPage((p) => Math.min(p + 1, lastPage))}
        disabled={page === lastPage || loading}
      >
        Siguiente
      </button>
    </div>
  );
}
