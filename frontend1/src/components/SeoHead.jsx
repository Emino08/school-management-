import { useEffect } from "react";

const isBrowser = typeof document !== "undefined";

const ensureMeta = ({ name, property, content }) => {
  if (!content || !isBrowser) {
    return;
  }

  const selector = name
    ? `meta[name="${name}"]`
    : property
      ? `meta[property="${property}"]`
      : null;

  if (!selector) {
    return;
  }

  let element = document.querySelector(selector);
  if (!element) {
    element = document.createElement("meta");
    if (name) {
      element.setAttribute("name", name);
    }
    if (property) {
      element.setAttribute("property", property);
    }
    document.head.appendChild(element);
  }
  element.setAttribute("content", content);
};

const ensureLink = ({ rel, href }) => {
  if (!href || !isBrowser) {
    return;
  }
  let element = document.querySelector(`link[rel="${rel}"]`);
  if (!element) {
    element = document.createElement("link");
    element.setAttribute("rel", rel);
    document.head.appendChild(element);
  }
  element.setAttribute("href", href);
};

const ensureStructuredData = (structuredData) => {
  if (!isBrowser) {
    return;
  }
  if (!structuredData) {
    const existing = document.getElementById("seo-structured-data");
    if (existing) {
      existing.remove();
    }
    return;
  }

  let script = document.getElementById("seo-structured-data");
  if (!script) {
    script = document.createElement("script");
    script.type = "application/ld+json";
    script.id = "seo-structured-data";
    document.head.appendChild(script);
  }
  script.text = JSON.stringify(structuredData);
};

const SeoHead = ({
  title = "Bo School | Unified School Management System",
  description = "Modernize academics, finance, and communications with Bo School's secure management platform.",
  keywords = "Bo School, school management, Sierra Leone education",
  image = "https://boschool.org/Bo-School-logo.png",
  url = "https://boschool.org/",
  type = "website",
  structuredData,
}) => {
  useEffect(() => {
    if (title) {
      document.title = title;
    }

    ensureMeta({ name: "description", content: description });
    ensureMeta({ name: "keywords", content: keywords });
    ensureMeta({ property: "og:title", content: title });
    ensureMeta({ property: "og:description", content: description });
    ensureMeta({ property: "og:image", content: image });
    ensureMeta({ property: "og:url", content: url });
    ensureMeta({ property: "og:type", content: type });
    ensureMeta({ name: "twitter:title", content: title });
    ensureMeta({ name: "twitter:description", content: description });
    ensureMeta({ name: "twitter:image", content: image });
    ensureMeta({ name: "twitter:card", content: "summary_large_image" });
    ensureLink({ rel: "canonical", href: url });
    ensureStructuredData(structuredData);

    return () => {
      // Remove structured data on unmount to prevent duplicates when navigating
      ensureStructuredData(null);
    };
  }, [title, description, keywords, image, url, type, structuredData]);

  return null;
};

export default SeoHead;
