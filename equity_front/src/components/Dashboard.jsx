import "./styles/Invoices.css";
import { useState } from "react";
import Sidebar from "./Layout/Sidebar";
import Header from "./Layout/Header";
import InvoiceList from "./Invoices/InvoiceList";
import InvoiceUpload from "./Invoices/InvoiceUpload";
import UserAdmin from "./Users/UserAdmin";

function WelcomeCard({ user }) {
  return (
    <div className="card welcome-card popCar">
      <h2>¡Bienvenido, {user.name}!</h2>
      <p>
        Este es tu panel de control. Aquí puedes ver tus facturas y gestionar
        usuarios si tienes permisos.
      </p>
    </div>
  );
}

function NoAccess() {
  return (
    <div style={{ padding: "2rem", textAlign: "center", color: "red" }}>
      <h2>No tienes permisos para estar aquí</h2>
    </div>
  );
}

function usePermissions(user) {
  return (perm) => user?.permissions?.includes(perm);
}

export default function Dashboard({ user }) {
  const [section, setSection] = useState("invoices");
  const hasPermission = usePermissions(user);

  const renderSection = () => {
    switch (section) {
      case "invoices":
        if (!hasPermission("view-invoices")) return <NoAccess />;
        return (
          <div className="flex small-col gap-4">
            <InvoiceList hasPermission={hasPermission} />
            {hasPermission("upload-invoices") && <InvoiceUpload />}
          </div>
        );

      case "users":
        return hasPermission("manage-users") ? (
          <UserAdmin currentUser={user} />
        ) : (
          <NoAccess />
        );

      default:
        return <NoAccess />;
    }
  };

  return (
    <div className="main-content">
      <div className="dashboard ">
        <Sidebar hasPermission={hasPermission} setSection={setSection} />
        <div className="main-area">
          <Header
            user={user}
            hasPermission={hasPermission}
            setSection={setSection}
          />
          <WelcomeCard user={user} />
          {renderSection()}
        </div>
      </div>
    </div>
  );
}
