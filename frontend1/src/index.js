import React from "react";
import ReactDOM from "react-dom/client";
import "./index.css";
import App from "./pages/admin/App";
import store from "./redux/store";
import { Provider } from "react-redux";
import { Toaster } from "sonner";

const root = ReactDOM.createRoot(document.getElementById("root"));
root.render(
  <React.StrictMode>
    <Provider store={store}>
      <App />
      <Toaster position="top-right" richColors expand={true} />
    </Provider>
  </React.StrictMode>,
);
