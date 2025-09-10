const NewUser = ({ handleCreate, newUser, setNewUser }) => {
  return (
    <div className="card user-card">
      <h3>Crear Nuevo Usuario</h3>
      <p>
        Rellena la información a continuación para agregar un nuevo usuario al
        sistema.
      </p>

      <form onSubmit={handleCreate} style={{ margin: "1rem 0" }}>
        <input
          type="text"
          placeholder="Nombre"
          value={newUser.name}
          onChange={(e) => setNewUser({ ...newUser, name: e.target.value })}
          required
        />
        <input
          type="email"
          placeholder="Email"
          value={newUser.email}
          onChange={(e) => setNewUser({ ...newUser, email: e.target.value })}
          required
        />
        <input
          type="password"
          placeholder="Contraseña"
          value={newUser.password}
          onChange={(e) => setNewUser({ ...newUser, password: e.target.value })}
          required
        />
        <button
          type="submit"
          disabled={!newUser.name || !newUser.email || !newUser.password}
        >
          Guardar
        </button>
      </form>
    </div>
  );
};

export default NewUser;
