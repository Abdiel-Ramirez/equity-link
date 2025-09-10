import { useState } from "react";
import "../styles/SideBar.css";

export default function Sidebar({ setSection, hasPermission }) {
  const [isOpen, setIsOpen] = useState();
  return (
    <>
      <aside className={`sidebar ${isOpen ? "oppened" : "closed"}`}>
        <ul className="flex flex-col sidebar-list">
          {hasPermission("view-invoices") && (
            <>
              <li className="big-items" onClick={() => setSection("invoices")}>
                Facturas
              </li>
              <li
                className="small-items"
                onClick={() => setSection("invoices")}
              >
                F
              </li>
            </>
          )}
          {/* {hasPermission("manage-users") && (
          <li onClick={() => setSection("users")}>Usuarios</li>
          )} */}
          <li className="grow hide"></li>
          <li
            onClick={() => setIsOpen((prev) => !prev)}
            className="isOpen-button"
          >
            <button>{`>`}</button>
          </li>
        </ul>
      </aside>
      <div
        className="change-width"
        style={{ width: isOpen ? "220px" : "60px", height: "100%" }}
      ></div>
    </>
  );
}
